<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CreateVersionService class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Service;

use Attachment\models\AttachmentModel;
use Convert\controllers\ConvertPdfController;
use Convert\models\AdrModel;
use Docserver\controllers\DocserverController;
use Exception;
use History\controllers\HistoryController;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\Problem\ParameterCannotBeEmptyProblem;
use MaarchCourrier\Core\Domain\SignatureBook\Port\CreateVersionServiceInterface;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use SrcCore\models\CoreConfigModel;

class CreateVersionService implements CreateVersionServiceInterface
{
    /**
     * @throws Exception
     */
    public function createVersionForResource(MainResourceInterface $mainResource, array $infos): array
    {
        if (empty($infos['encodedFile'])) {
            return ['errors' => '[CreateVersionService createVersionForResource] encodedFile is mandatory'];
        }

        if (empty($infos['format'])) {
            return ['errors' => '[CreateVersionService createVersionForResource] format is mandatory'];
        }

        if (!empty($mainResource->getFilename())) {
            AdrModel::createDocumentAdr([
                'resId'        => $mainResource->getResId(),
                'type'         => 'DOC',
                'docserverId'  => $mainResource->getDocument()->getDocserverId(),
                'path'         => $mainResource->getDocument()->getPath(),
                'filename'     => $mainResource->getFilename(),
                'version'      => $mainResource->getVersion(),
                'fingerprint'  => $mainResource->getFingerprint(),
                'is_annotated' => $mainResource->isAnnotated()
            ]);
        }

        $infos['resId'] = $mainResource->getResId();
        $storedResource = StoreController::storeResource($infos);
        if (!empty($storedResource['errors'])) {
            return ['errors' => '[CreateVersionService createVersionForResource] ' . $storedResource['errors']];
        }

        ConvertPdfController::convert([
            'encodedFile' => $infos['encodedFile'],
            'format'      => $infos['format'],
            'resId'       => $infos['resId'],
            'collId'      => 'letterbox_coll',
            'version'     => $mainResource->getVersion() + 1
        ]);

        if ((isset($infos['isAnnotated']) && $infos['isAnnotated'] === true)) {
            $mainResource->setIsAnnotated(true);
        } else {
            $mainResource->setIsAnnotated(false);
        }

        $customId = CoreConfigModel::getCustomId();
        $customId = empty($customId) ? 'null' : $customId;
        exec(
            "php src/app/convert/scripts/FullTextScript.php --customId {$customId} " .
            "--resId {$infos['resId']} --collId letterbox_coll --userId {$GLOBALS['id']} > /dev/null &"
        );

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $infos['resId'],
            'eventType' => 'UP',
            'info'      => _FILE_UPDATED . " : {$mainResource->getChrono()}",
            'moduleId'  => 'resource',
            'eventId'   => 'fileModification'
        ]);

        return ['success' => true];
    }

    /**
     * @param MainResourceInterface $mainResource
     * @param array $infos
     * @throws ParameterCannotBeEmptyProblem
     * @throws Exception
     */
    public function createSignedVersionForResource(MainResourceInterface $mainResource, array $infos): void
    {
        if (empty($infos['encodedFile'])) {
            throw new ParameterCannotBeEmptyProblem('encodedFile');
        }

        if (empty($infos['format'])) {
            throw new ParameterCannotBeEmptyProblem('format');
        }

        $storeResult = DocserverController::storeResourceOnDocServer([
            'collId'          => 'letterbox_coll',
            'docserverTypeId' => 'DOC',
            'encodedResource' => $infos['encodedFile'],
            'format'          => strtolower($infos['format'])
        ]);

        AdrModel::createDocumentAdr([
            'resId'          => $mainResource->getResId(),
            'type'           => 'SIGN',
            'docserverId'    => $storeResult['docserver_id'],
            'path'           => $storeResult['directory'],
            'filename'       => $storeResult['file_destination_name'],
            'version'        => $mainResource->getVersion() + 1,
            'fingerprint'    => $storeResult['fingerPrint'],
            'is_annotated'   => $mainResource->isAnnotated()
        ]);

        ResModel::update([
            'set' => ['version' => $mainResource->getVersion() + 1],
            'where' => ['res_id = ?'],
            'data' => [$mainResource->getResId()]
        ]);
    }


    /**
     * @param AttachmentInterface $attachment
     * @param array $infos
     * @return array
     * @throws ParameterCannotBeEmptyProblem
     * @throws Exception
     */
    public function createVersionForAttachment(AttachmentInterface $attachment, array $infos): array
    {
        if (empty($infos['encodedFile'])) {
            return ['errors' => '[CreateVersionService createVersionForResource] encodedFile is mandatory'];
        }

        if (empty($infos['format'])) {
            return ['errors' => '[CreateVersionService createVersionForResource] format is mandatory'];
        }

        $originId = $attachment->getOriginId() ?? $attachment->getResId();

        if ((isset($infos['isAnnotated']) && $infos['isAnnotated'] === true)) {
            $attachment->setIsAnnotated(true);
        } else {
            $attachment->setIsAnnotated(false);
        }

        $type = $attachment->getType()->getType();
        if (isset($infos['isSignedFile']) && $infos['isSignedFile'] === true) {
            $type = 'signed_response';
        }

        $data = [
            'title'               => $attachment->getTitle(),
            'encodedFile'         => $infos['encodedFile'],
            'status'              => $attachment->getStatus(),
            'format'              => $infos['format'],
            'typist'              => $attachment->getTypist()->getId(),
            'resIdMaster'         => $attachment->getMainResource()->getResId(),
            'chrono'              => $attachment->getChrono(),
            'type'                => $type,
            'originId'            => $originId,
            'recipientId'         => !empty($attachment->getRecipient()) ? $attachment->getRecipient()->getId() : null,
            'recipientType'       => $attachment->getRecipientType(),
            'inSignatureBook'     => true,
            'signature_positions' => $attachment->getSignaturePositions(),
            'templateId'          => (!empty($attachment->getTemplate())) ? $attachment->getTemplate()->getId() : null,
            'is_annotated'        => $attachment->isAnnotated(),
        ];

        $id = StoreController::storeAttachment($data);
        if (!empty($id['errors'])) {
            return ['errors' => $id['errors']];
        }

        $setValues = [];
        $setValues['external_state'] = json_encode($attachment->getExternalState());

        if (!empty($attachment->getExternalDocumentId())) {
            $externalId = [
                'internalParapheur' => $attachment->getExternalDocumentId()
            ];
            $setValues['external_id'] = json_encode($externalId);
        }

        AttachmentModel::update([
            'set'   => $setValues,
            'where' => ['res_id = ?'],
            'data'  => [$id]
        ]);

        return ['newId' => $id];
    }
}
