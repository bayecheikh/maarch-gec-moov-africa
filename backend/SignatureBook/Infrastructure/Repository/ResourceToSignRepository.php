<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource To Sign Repository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Repository;

use Attachment\models\AttachmentModel;
use Convert\models\AdrModel;
use Exception;
use MaarchCourrier\SignatureBook\Domain\Port\ResourceToSignRepositoryInterface;
use Resource\models\ResModel;

class ResourceToSignRepository implements ResourceToSignRepositoryInterface
{
    /**
     * @param int $resId
     * @return array
     * @throws Exception
     */
    public function getResourceInformations(int $resId): array
    {
        return ResModel::getById([
            'resId'  => $resId,
            'select' => [
                '*'
            ]
        ]);
    }

    /**
     * @param int $resId
     * @return array
     * @throws Exception
     */
    public function getAttachmentInformations(int $resId): array
    {
        return AttachmentModel::getById([
            'id'     => $resId,
            'select' =>
                [
                    'res_id_master',
                    'title',
                    'typist',
                    'identifier',
                    'recipient_id',
                    'recipient_type',
                    'format',
                    'status',
                    'origin',
                    'origin_id',
                    'relation',
                    'attachment_type',
                    'external_id'
                ]
        ]);
    }

    /**
     * @param int $resId
     * @param array $storeInformations
     * @return void
     * @throws Exception
     */
    public function createIntermediateSignedVersionForResource(int $resId, array $storeInformations): void
    {
        $infosResource = $this->getResourceInformations($resId);

        $version = (int)$infosResource['version'] + 1;
        //Création ADR version courante
        AdrModel::createDocumentAdr([
            'resId'       => $resId,
            'type'        => 'DOC',
            'docserverId' => $infosResource['docserver_id'],
            'path'        => $infosResource['path'],
            'filename'    => $infosResource['filename'],
            'version'     => $infosResource['version'],
            'fingerprint' => $infosResource['fingerprint']
        ]);


        //Modifier les infos pour la pièce courante
        $setValues = [
            'version'      => $version,
            'docserver_id' => $storeInformations['docserver_id'],
            'path'         => $storeInformations['directory'],
            'filename'     => $storeInformations['file_destination_name'],
            'fingerprint'  => $storeInformations['fingerPrint'],
            'format'       => 'pdf'
        ];
        $this->setResourceInformations($resId, $setValues);

        AdrModel::deleteDocumentAdr([
            'where' => ['res_id = ?', 'type = ?', 'version = ?'],
            'data'  => [$resId, 'TNL', $version]
        ]);
    }

    /**
     * @param int $resId
     * @param array $storeInformations
     * @return void
     * @throws Exception
     */
    public function createSignVersionForResource(int $resId, array $storeInformations): void
    {
        $infosResource = $this->getResourceInformations($resId);
        $version = (int)$infosResource['version'] + 1;

        AdrModel::createDocumentAdr([
            'resId'       => $resId,
            'type'        => 'SIGN',
            'docserverId' => $storeInformations['docserver_id'],
            'path'        => $storeInformations['directory'],
            'filename'    => $storeInformations['file_destination_name'],
            'version'     => $version,
            'fingerprint' => $storeInformations['fingerPrint']
        ]);

        //Mise à jour de la version letterbox
        $setValues = ['version' => $version];
        $this->setResourceInformations($resId, $setValues);

        AdrModel::deleteDocumentAdr([
            'where' => ['res_id = ?', 'type = ?', 'version = ?'],
            'data'  => [$resId, 'TNL', $version]
        ]);
    }

    /**
     * @param int $resId
     * @return bool
     * @throws Exception
     */
    public function isResourceSigned(int $resId): bool
    {
        $signedDocument = AdrModel::getDocuments([
            'select' => ['id'],
            'where'  => ['res_id = ?', 'type = ?'],
            'data'   => [$resId, 'SIGN'],
            'limit'  => 1
        ]);

        return (!empty($signedDocument));
    }

    /**
     * @param int $resId
     * @return bool
     * @throws Exception
     */
    public function isAttachementSigned(int $resId): bool
    {
        $infos = $this->getAttachmentInformations($resId);
        return ($infos['status'] === 'SIGN');
    }

    /**
     * @param int $resId
     * @param int $parapheurDocumentId
     * @return void
     * @throws Exception
     */
    public function setResourceExternalId(int $resId, int $parapheurDocumentId): void
    {
        $externalId = [
            'internalParapheur' => $parapheurDocumentId
        ];

        ResModel::update([
            'set'   => ['external_id' => json_encode($externalId)],
            'where' => ['res_id = ?'],
            'data'  => [$resId]
        ]);
    }

    /**
     * @param int $resId
     * @param array $setValues
     * @return void
     * @throws Exception
     */
    public function setResourceInformations(int $resId, array $setValues): void
    {
        ResModel::update([
            'set'   => $setValues,
            'where' => ['res_id = ?'],
            'data'  => [$resId]
        ]);
    }
}
