<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource data DB
 * @author dev@maarch.org
 */

namespace Resource\Infrastructure;

use Attachment\models\AttachmentModel;
use Convert\controllers\ConvertPdfController;
use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Exception;
use MaarchCourrier\Core\Domain\MainResource\Port\ResourceRepositoryInterface;
use Resource\controllers\ResController;
use Resource\Domain\Docserver;
use Resource\Domain\Resource;
use Resource\Domain\ResourceConverted;
use Resource\models\ResModel;
use SrcCore\models\TextFormatModel;

class ResourceData implements ResourceRepositoryInterface
{
    public function getMainResourceData(int $resId): ?Resource
    {
        $resource = ResModel::getById(['resId'  => $resId, 'select' => ['*']]);

        if (empty($resource)) {
            return null;
        }

        return Resource::createFromArray($resource);
    }

    public function getSignResourceData(int $resId, int $version): ?ResourceConverted
    {
        $resource = AdrModel::getDocuments([
            'select' => ['*'],
            'where'  => ['res_id = ?', 'type = ?', 'version = ?'],
            'data'   => [$resId, 'SIGN', $version],
            'limit'  => 1
        ]);

        if (empty($resource[0])) {
            return null;
        }

        return new ResourceConverted(
            $resource[0]['id'],
            $resource[0]['res_id'],
            $resource[0]['type'],
            $resource[0]['version'],
            $resource[0]['docserver_id'],
            $resource[0]['path'],
            $resource[0]['filename'],
            $resource[0]['fingerprint']
        );
    }

    public function getDocserverDataByDocserverId(string $docserverId): ?Docserver
    {
        if (empty($docserverId)) {
            return null;
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $docserverId, 'select' => ['*']]);

        if (empty($docserver)) {
            return null;
        }

        return new Docserver(
            $docserver['id'],
            $docserver['docserver_id'],
            $docserver['docserver_type_id'],
            $docserver['path_template'],
            $docserver['is_encrypted']
        );
    }

    public function updateFingerprint(int $resId, string $fingerprint): void
    {
        ResModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$resId]]);
    }

    public function formatFilename(string $name, int $maxLength = 250): string
    {
        return TextFormatModel::formatFilename(['filename' => $name, 'maxLength' => 250]);
    }

    /**
     * Return the converted pdf from resource
     *
     * @param   int     $resId  Resource id
     * @param   string  $collId Resource type id : letterbox_coll or attachments_coll
     */
    public function getConvertedPdfById(int $resId, string $collId): array
    {
        return ConvertPdfController::getConvertedPdfById(['resId' => $resId, 'collId' => $collId]);
    }

    /**
     * @param   int     $resId      Resource id
     * @param   string  $type       Resource converted format
     * @param   int     $version    Resource version
     *
     * @return  ?array
     */
    public function getResourceVersion(int $resId, string $type, int $version): ?array
    {
        $document = AdrModel::getDocuments([
            'select'    => ['id', 'docserver_id', 'path', 'filename', 'fingerprint'],
            'where'     => ['res_id = ?', 'type = ?', 'version = ?'],
            'data'      => [$resId, $type, $version]
        ]);

        if (empty($document[0])) {
            return null;
        }

        return $document[0];
    }

    /**
     * @param   int     $resId  Resource id
     * @param   string  $type   Resource converted format
     *
     * @return  ResourceConverted
     */
    public function getLatestResourceVersion(int $resId, string $type): ?ResourceConverted
    {
        $document = AdrModel::getDocuments([
            'select'    => ['id', 'version', 'docserver_id', 'path', 'filename', 'fingerprint'],
            'where'     => ['res_id = ?', 'type = ?'],
            'data'      => [$resId, $type],
            'orderBy'   => ['version desc']
        ]);

        if (empty($document[0])) {
            return null;
        }

        return new ResourceConverted(
            $document[0]['id'],
            $resId,
            $type,
            $document[0]['version'],
            $document[0]['docserver_id'],
            $document[0]['path'],
            $document[0]['filename'],
            $document[0]['fingerprint']
        );
    }

    /**
     * @param   int $resId      Resource id
     * @param   int $version    Resource version
     *
     * @return  ResourceConverted
     */
    public function getLatestPdfVersion(int $resId, int $version): ?ResourceConverted
    {
        $document = AdrModel::getDocuments([
            'select'    => ['id', 'version', 'type', 'docserver_id', 'path', 'filename', 'fingerprint'],
            'where'     => ['res_id = ?', 'type in (?)', 'version = ?'],
            'data'      => [$resId, ['PDF', 'SIGN'], $version],
            'orderBy'   => ["type='SIGN' DESC"],
            'limit'     => 1
        ]);

        if (empty($document[0])) {
            return null;
        }

        return new ResourceConverted(
            $document[0]['id'],
            $resId,
            $document[0]['type'],
            $document[0]['version'],
            $document[0]['docserver_id'],
            $document[0]['path'],
            $document[0]['filename'],
            $document[0]['fingerprint']
        );
    }

    /**
     * Check if user has rights over the resource
     *
     * @param   int     $resId      Resource id
     * @param   int     $userId     User id
     *
     * @return  bool
     */
    public function hasRightByResId(int $resId, int $userId): bool
    {
        return ResController::hasRightByResId(['resId' => [$resId], 'userId' => $userId]);
    }

    /**
     * @param  string  $filename  Filename to search for in main resources and attachments
     *
     * @return  string|null Alt identifier associated with the filename, or null if not found
     * @throws Exception
     */
    public function getAltIdentifierByFilename(string $filename): ?string
    {
        // Test for resource
        $resource = ResModel::get([
            'select' => ['alt_identifier'],
            'where'  => ['filename = ?'],
            'data'   => [$filename]
        ]);

        if (!empty($resource[0]['alt_identifier'])) {
            return $resource[0]['alt_identifier'];
        }

        $adrResource = AdrModel::getDocuments([
            'select' => ['res_id'],
            'where'  => ['filename = ?'],
            'data'   => [$filename]
        ]);

        if (!empty($adrResource[0]['res_id'])) {
            $resource = ResModel::getById([
                'resId'  => $adrResource[0]['res_id'],
                'select' => ['alt_identifier']
            ]);
            return $resource['alt_identifier'];
        }

        // Test for attachment
        $attachment = AttachmentModel::get([
            'select' => ['identifier'],
            'where'  => ['filename = ?'],
            'data'   => [$filename]
        ]);
        if (!empty($attachment[0]['identifier'])) {
            return $attachment[0]['identifier'];
        }

        $adrAttachment = AdrModel::getAttachments([
            'select' => ['res_id'],
            'where'  => ['filename = ?'],
            'data'   => [$filename]
        ]);
        if (!empty($adrAttachment[0]['res_id'])) {
            $attachment = AttachmentModel::getById([
                'id'     => $adrAttachment[0]['res_id'],
                'select' => ['identifier']
            ]);
            return $attachment['identifier'];
        }

        return null;
    }
}
