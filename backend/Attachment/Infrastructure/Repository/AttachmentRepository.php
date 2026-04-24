<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief AttachmentRepository class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Attachment\Infrastructure\Repository;

use Attachment\models\AttachmentModel;
use Attachment\models\AttachmentTypeModel;
use DateTimeImmutable;
use Exception;
use MaarchCourrier\Attachment\Domain\Attachment;
use MaarchCourrier\Attachment\Domain\AttachmentType;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\Contact\Port\ContactRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\Template\Port\TemplateRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\DocumentStorage\Domain\Document;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    /**
     * @param UserRepositoryInterface $userRepository
     * @param MainResourceRepositoryInterface $mainResourceRepository
     * @param TemplateRepositoryInterface $templateRepository
     * @param ContactRepositoryInterface $contactRepository
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly ContactRepositoryInterface $contactRepository
    ) {
    }

    /**
     * @param MainResourceInterface $mainResource
     * @return Attachment[]
     * @throws Exception
     */
    public function getAttachmentsInSignatureBookByMainResource(MainResourceInterface $mainResource): array
    {
        $where = [
            'res_id_master = ?',
            'in_signature_book = ?',
            "status not in ('DEL', 'TMP', 'OBS', 'SIGN')"
        ];
        $data = [$mainResource->getResId(), 'true'];

        return $this->fetchAndMapAttachments($mainResource, $where, $data);
    }

    /**
     * @param MainResourceInterface $mainResource
     *
     * @return Attachment[]
     * @throws Exception
     */
    public function getAttachmentsWithAnInternalParapheur(MainResourceInterface $mainResource): array
    {
        $where = [
            'res_id_master = ?',
            'in_signature_book = ?',
            "status not in ('DEL', 'TMP', 'OBS')",
            "external_id->>'internalParapheur' IS NOT NULL"
        ];
        $data = [$mainResource->getResId(), 'true'];

        return $this->fetchAndMapAttachments($mainResource, $where, $data);
    }

    /**
     * @param int $resId
     *
     * @return AttachmentInterface|null
     * @throws Exception
     */
    public function getAttachmentByResId(int $resId): ?AttachmentInterface
    {
        $attachmentTypes = $this->getAttachmentTypes();

        $data = AttachmentModel::getById([
            'id'     => $resId,
            'select' => ['*']
        ]);

        if (empty($data)) {
            return null;
        }

        return $this->mapAttachment($data, $attachmentTypes);
    }

    /**
     * @param AttachmentInterface $attachment
     * @param array $values
     *
     * @return AttachmentInterface
     * @throws Exception
     */
    public function updateAttachment(AttachmentInterface $attachment, array $values): AttachmentInterface
    {
        AttachmentModel::update([
            'set'   => $values,
            'where' => ['res_id = ?'],
            'data'  => [$attachment->getResId()]
        ]);

        $this->updateAttachmentProperties($attachment, $values);
        return $attachment;
    }

    /**
     * @param AttachmentInterface $attachment
     *
     * @return bool
     */
    public function isSigned(AttachmentInterface $attachment): bool
    {
        return ($attachment->getStatus() === 'SIGN');
    }

    /**
     * @param AttachmentInterface $attachment
     * @param MainResourceInterface $mainResource
     *
     * @return bool
     */
    public function checkConcordanceResIdAndResIdMaster(
        AttachmentInterface $attachment,
        MainResourceInterface $mainResource
    ): bool {
        return ($attachment->getMainResource()->getResId() === $mainResource->getResId());
    }

    /**
     * @param AttachmentInterface $attachment
     *
     * @return void
     * @throws Exception
     */
    public function removeSignatureBookLink(AttachmentInterface $attachment): void
    {
        AttachmentModel::update([
            'postSet' => ['external_id' => "external_id - 'internalParapheur'"],
            'where'   => ['res_id = ?'],
            'data'    => [$attachment->getResId()]
        ]);
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getAttachmentTypes(): array
    {
        $attachmentTypes = AttachmentTypeModel::get(['select' => ['type_id', 'label', 'signable']]);
        $attachmentTypes = array_column($attachmentTypes, null, 'type_id');

        foreach ($attachmentTypes as $type => $attachmentType) {
            $attachmentTypes[$type] = (new AttachmentType())
                ->setType($type)
                ->setLabel($attachmentType['label'])
                ->setSignable($attachmentType['signable']);
        }

        return $attachmentTypes;
    }

    /**
     * @param MainResourceInterface $mainResource
     * @param array $where
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    private function fetchAndMapAttachments(MainResourceInterface $mainResource, array $where, array $data): array
    {
        $attachmentTypes = $this->getAttachmentTypes();

        $attachmentsData = AttachmentModel::get([
            'select' => ['*'],
            'where'  => $where,
            'data'   => $data
        ]);

        return $this->mapAttachments($attachmentsData, $attachmentTypes, $mainResource);
    }

    /**
     * @param array $attachmentsData
     * @param array $attachmentTypes
     * @param MainResourceInterface $mainResource
     *
     * @return array
     * @throws Exception
     */
    private function mapAttachments(
        array $attachmentsData,
        array $attachmentTypes,
        MainResourceInterface $mainResource
    ): array {
        $attachments = [];
        foreach ($attachmentsData as $data) {
            $attachments[] = $this->mapAttachment($data, $attachmentTypes, $mainResource);
        }
        return $attachments;
    }

    /**
     * @param array $data
     * @param array $attachmentTypes
     * @param MainResourceInterface|null $mainResource If null then we fetch main resource
     *
     * @return Attachment
     * @throws Exception
     */
    private function mapAttachment(
        array $data,
        array $attachmentTypes,
        MainResourceInterface $mainResource = null
    ): Attachment {
        $typist = $this->userRepository->getUserById((int)$data['typist']);
        if (empty($mainResource)) {
            $mainResource = $this->mainResourceRepository->getMainResourceByResId($data['res_id_master']);
        }

        $versions = $this->fetchOlderAttachmentVersions($data['origin_id'], $mainResource->getResId());

        $document = (new Document())
            ->setFileName($data['filename'])
            ->setFileExtension($data['format'])
            ->setPath($data['path'])
            ->setDocserverId($data['docserver_id'])
            ->setFingerprint($data['fingerprint']);

        $externalId = json_decode($data['external_id'], true);
        $externalDocumentId = null;

        if (!empty($externalId)) {
            $externalDocumentId = isset($externalId['internalParapheur']) ?
                (int)$externalId['internalParapheur'] : null;
        }

        $template = (!empty($data['template_id'])) ?
            $this->templateRepository->getById($data['template_id']) : null;

        $hasDigitalSignature = json_decode($data['external_state'], true)['hasDigitalSignature'] ?? false;

        $recipient = null;
        if ($data['recipient_id'] != null) {
            if ($data['recipient_type'] == 'user') {
                $recipient = $this->userRepository->getUserById($data['recipient_id']);
            } elseif ($data['recipient_type'] == 'contact') {
                $recipient = $this->contactRepository->getById($data['recipient_id']);
            }
        }

        return (new Attachment())
            ->setResId($data['res_id'])
            ->setMainResource($mainResource)
            ->setTitle($data['title'])
            ->setChrono($data['identifier'] ?? '')
            ->setTypist($typist)
            ->setRelation($data['relation'])
            ->setType($attachmentTypes[$data['attachment_type']])
            ->setDocument($document)
            ->setExternalDocumentId($externalDocumentId)
            ->setExternalId($externalId)
            ->setExternalState(json_decode($data['external_state'], true))
            ->setRecipient($recipient)
            ->setStatus($data['status'])
            ->setOriginId($data['origin_id'])
            ->setVersion($data['relation'])
            ->setCreationDate(new DateTimeImmutable($data['creation_date']))
            ->setModificationDate(new DateTimeImmutable($data['modification_date']))
            ->setTemplate($template)
            ->setSignaturePositions(json_decode($data['signature_positions'], true) ?? [])
            ->setHasDigitalSignature($hasDigitalSignature)
            ->setVersions($versions)
            ->setIsAnnotated($data['is_annotated']);
    }

    /**
     * @throws Exception
     */
    private function fetchOlderAttachmentVersions(?int $attachment, int $args): array
    {
        if (empty($attachment)) {
            return [];
        }

        return AttachmentModel::get([
            'select'  => ['res_id as "resId"', 'relation'],
            'where'   => ['(origin_id = ? OR res_id = ?)', 'res_id != ?', 'status not in (?)'],
            'data'    => [$attachment, $attachment, $args, ['DEL']],
            'orderBy' => ['relation DESC']
        ]);
    }


    /**
     * @param string|null $externalId
     *
     * @return int|null
     */
    private function getInternalParapheurFromExternalId(?string $externalId): ?int
    {
        $decoded = json_decode($externalId, true);
        return !empty($decoded['internalParapheur']) ? (int)$decoded['internalParapheur'] : null;
    }

    /**
     * @param AttachmentInterface $attachment
     * @param array $values
     *
     * @return void
     */
    private function updateAttachmentProperties(AttachmentInterface $attachment, array $values): void
    {
        foreach ($values as $key => $value) {
            switch ($key) {
                case 'title':
                    $attachment->setTitle($value);
                    break;
                case 'status':
                    $attachment->setStatus($value);
                    break;
                case 'relation':
                    $attachment->setRelation($value);
                    break;
                case 'external_id':
                    $attachment->setExternalDocumentId($this->getInternalParapheurFromExternalId($value));
                    break;
                case 'external_state':
                    $attachment->setExternalState(json_decode($value, true));
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @param int $resId
     * @return AttachmentInterface|null
     * @throws Exception
     */
    public function getLastNotAnnotatedAttachmentByResId(int $resId): ?AttachmentInterface
    {
        $attachmentTypes = $this->getAttachmentTypes();

        $data = AttachmentModel::getById([
            'id'     => $resId,
            'select' => ['*']
        ]);

        if (empty($data)) {
            return null;
        }

        if (empty($data['origin_id'])) {
            return $this->mapAttachment($data, $attachmentTypes);
        }

        $data = AttachmentModel::get([
            'select'  => ['*'],
            'where'   => ['res_id = ? OR origin_id = ?'],
            'data'    => [$data['origin_id'], $data['origin_id']],
            'orderBy' => ['relation DESC']
        ]);

        $attachmentData = [];

        foreach ($data as $attachment) {
            if (!$attachment['is_annotated']) {
                $attachmentData = $attachment;
                break;
            }
        }

        if (empty($attachmentData)) {
            return null;
        }

        return $this->mapAttachment($attachmentData, $attachmentTypes);
    }

    /**
     * Get the sign attachment from the table res_attachments
     *
     * @param int $originId
     *
     * @return AttachmentInterface|null
     * @throws Exception
     */
    public function getSignAttachmentByOriginId(int $originId): ?AttachmentInterface
    {
        $attachmentTypes = $this->getAttachmentTypes();

        $data = AttachmentModel::get([
            'select' => ['*'],
            'where'  => ['origin = ?', 'status not in (?)', 'attachment_type = ?'],
            'data'   => ["$originId,res_attachments", ['OBS', 'DEL', 'TMP', 'FRZ'], 'signed_response']
        ]);

        if (empty($data)) {
            return null;
        }

        return $this->mapAttachment($data[0], $attachmentTypes);
    }

    /**
     * @return AttachmentInterface[]
     * @throws Exception
     *
     */
    public function getAttachmentsForMaileva(MainResourceInterface $mainResource): array
    {
        $attachments = [];
        $attachmentTypes = $this->getAttachmentTypes();

        $data = AttachmentModel::get([
            'select' => ['*'],
            'where'  => ['res_id_master = ?', 'in_send_attach = ?', 'status not in (?)'],
            'data'   => [$mainResource->getResId(), true, ['OBS', 'DEL', 'TMP', 'FRZ']]
        ]);

        foreach ($data as $attachment) {
            $attachments[] = $this->mapAttachment($attachment, $attachmentTypes, $mainResource);
        }

        return $attachments;
    }
}
