<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MainResourceRepository class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\MainResource\Infrastructure\Repository;

use Convert\models\AdrModel;
use DateTimeImmutable;
use Exception;
use MaarchCourrier\Core\Domain\Entity\Port\EntityRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\Template\Port\TemplateRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\DocumentStorage\Domain\Document;
use MaarchCourrier\MainResource\Domain\Integration;
use MaarchCourrier\MainResource\Domain\MainResource;
use Resource\models\ResModel;
use SrcCore\models\DatabaseModel;

class MainResourceRepository implements MainResourceRepositoryInterface
{
    /**
     * @param UserRepositoryInterface $userRepository
     * @param TemplateRepositoryInterface $templateRepository
     * @param EntityRepositoryInterface $entityRepository
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly EntityRepositoryInterface $entityRepository
    ) {
    }

    /**
     * @param MainResourceInterface $mainResource
     * @param string $basketClause
     * @return bool
     */
    public function doesExistOnView(MainResourceInterface $mainResource, string $basketClause): bool
    {
        try {
            $res = DatabaseModel::select([
                'select' => [1],
                'table'  => ['res_view_letterbox'],
                'where'  => ['res_id = ?', "($basketClause)"],
                'data'   => [$mainResource->getResId()]
            ]);
        } catch (Exception) {
            return false;
        }

        if (empty($res)) {
            return false;
        }

        return true;
    }

    /**
     * @param int $resId
     * @return ?MainResourceInterface
     * @throws Exception
     */
    public function getMainResourceByResId(int $resId): ?MainResourceInterface
    {
        $mainResources = $this->getMainResourcesByResIds([$resId]);
        return $mainResources[0] ?? null;
    }

    /**
     * @param int[] $resIds
     * @return MainResourceInterface[]
     * @throws Exception
     */
    public function getMainResourcesByResIds(array $resIds): array
    {
        if (empty($resIds)) {
            return [];
        }

        $resources = ResModel::get([
            'select' => ['*'],
            'where'  => ['res_id in (?)'],
            'data'   => [$resIds]
        ]);

        if (empty($resources)) {
            return [];
        }

        $typistIds = array_unique(array_filter(array_column($resources, 'typist')));
        $users = $this->userRepository->getUsersByIds($typistIds);

        $templateIds = array_unique(array_filter(array_column($resources, 'template_id')));
        $templates = $this->templateRepository->getByIds($templateIds);

        $destinationIds = array_unique(array_filter(array_column($resources, 'destination')));
        $destinations = $this->entityRepository->getEntityByEntityIds($destinationIds);

        $versionsFromDb = AdrModel::getDocuments([
            'select' => ['res_id as "resId"', 'version as relation'],
            'where'  => ['res_id in (?)', 'type = ?'],
            'data'   => [$resIds, 'PDF']
        ]);

        $versions = [];
        foreach ($versionsFromDb as $version) {
            $versions[$version['resId']][] = $version;
        }

        $mainResources = [];
        foreach ($resources as $resource) {
            $resId = (int)$resource['res_id'];

            $document = (new Document())
                ->setFileName($resource['filename'] ?? '')
                ->setFileExtension($resource['format'] ?? '')
                ->setFingerprint($resource['fingerprint'] ?? '')
                ->setPath($resource['path'] ?? '')
                ->setDocserverId($resource['docserver_id'] ?? '');

            $integration = (new Integration())->createFromArray(json_decode($resource['integrations'], true));
            $externalId = json_decode($resource['external_id'], true);
            $externalDocumentId = $externalId['internalParapheur'] ?? null;

            $mainResource = (new MainResource())
                ->setResId($resId)
                ->setSubject($resource['subject'])
                ->setChrono($resource['alt_identifier'])
                ->setTypist($users[$resource['typist']] ?? null)
                ->setDocument($document)
                ->setIntegration($integration)
                ->setExternalDocumentId($externalDocumentId)
                ->setExternalId($externalId)
                ->setExternalState(json_decode($resource['external_state'], true))
                ->setVersion($resource['version'])
                ->setCreationDate(new DateTimeImmutable($resource['creation_date']))
                ->setModificationDate(new DateTimeImmutable($resource['modification_date']))
                ->setTemplate($templates[$resource['template_id']] ?? null)
                ->setSignaturePositions(json_decode($resource['signature_positions'], true) ?? [])
                ->setHasDigitalSignature(json_decode($resource['external_state'], true)['hasDigitalSignature'] ?? false)
                ->setVersions($versions[$resId] ?? [])
                ->setIsAnnotated($resource['is_annotated'] ?? false)
                ->setDestination($destinations[$resource['destination']] ?? null);

            $mainResources[] = $mainResource;
        }

        return $mainResources;
    }

    /**
     * @param MainResourceInterface $mainResource
     * @return bool
     * @throws Exception
     */
    public function isMainResourceSigned(MainResourceInterface $mainResource): bool
    {
        $signedDocument = AdrModel::getDocuments([
            'select' => ['id'],
            'where'  => ['res_id = ?', 'type = ?'],
            'data'   => [$mainResource->getResId(), 'SIGN'],
            'limit'  => 1
        ]);

        return (!empty($signedDocument));
    }

    /**
     * @param MainResourceInterface $mainResource
     * @return void
     * @throws Exception
     */
    public function removeSignatureBookLink(MainResourceInterface $mainResource): void
    {
        ResModel::update([
            'postSet' => ['external_id' => "external_id - 'internalParapheur'"],
            'where'   => ['res_id = ?'],
            'data'    => [$mainResource->getResId()]
        ]);
    }

    /**
     * @param int $resId
     * @return int|null
     * @throws Exception
     */
    public function getLastNotAnnotatedResourceVersionByResId(int $resId): ?int
    {
        $resource = ResModel::getById(['resId' => $resId, 'select' => ['version', 'is_annotated']]);
        if (!$resource['is_annotated']) {
            return $resource['version'];
        }

        $data = AdrModel::getDocuments([
            'select'  => ['version', 'is_annotated'],
            'where'   => ['res_id = ?', 'type = ?'],
            'data'    => [$resId, 'DOC'],
            'orderBy' => ['version DESC']
        ]);

        foreach ($data as $dataVersion) {
            if (!$dataVersion['is_annotated']) {
                return $dataVersion['version'];
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getDocumentByResIdAndVersion(int $resId, int $version): ?Document
    {
        $resource = ResModel::getById([
            'resId'  => $resId,
            'select' => ['filename', 'format', 'fingerprint', 'path', 'docserver_id', 'version']
        ]);

        if (!empty($resource)) {
            if ($resource['version'] == $version) {
                $document = (new Document())
                    ->setFileName($resource['filename'] ?? '')
                    ->setFileExtension($resource['format'] ?? '')
                    ->setFingerprint($resource['fingerprint'] ?? '')
                    ->setPath($resource['path'] ?? '')
                    ->setDocserverId($resource['docserver_id'] ?? '');
            } else {
                $data = AdrModel::getDocuments([
                    'select' => ['filename', 'fingerprint', 'path', 'docserver_id'],
                    'where'  => ['res_id = ?', 'version = ?', 'type = ?'],
                    'data'   => [$resId, $version, 'DOC']
                ]);

                $extension = pathinfo($data[0]['filename'] ?? '', PATHINFO_EXTENSION);

                $document = (new Document())
                    ->setFileName($data[0]['filename'] ?? '')
                    ->setFileExtension($extension)
                    ->setFingerprint($data[0]['fingerprint'] ?? '')
                    ->setPath($data[0]['path'] ?? '')
                    ->setDocserverId($data[0]['docserver_id'] ?? '');
            }

            return $document;
        }

        return null;
    }

    /**
     * @return MainResourceInterface[]
     * @throws Exception
     */
    public function getOnViewByClause(array $where, array $data): array
    {
        $resourceIds = ResModel::getOnView([
            'select' => ['res_id'],
            'where'  => $where,
            'data'   => $data
        ]);

        $resourceIds = array_column($resourceIds, 'res_id');

        return $this->getMainResourcesByResIds($resourceIds);
    }

    /**
     * @throws Exception
     */
    public function updateMainResourceStatus(MainResourceInterface $mainResource, string $status): void
    {
        ResModel::update([
            'set'   => ['status' => $status],
            'where' => ['res_id = ?'],
            'data'  => [$mainResource->getResId()]
        ]);
    }
}
