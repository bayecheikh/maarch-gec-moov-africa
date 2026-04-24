<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief External Export Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Infrastructure\Service;

use Attachment\models\AttachmentModel;
use Exception;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Port\ExternalFieldUpdaterServiceInterface;
use Resource\models\ResModel;

class ExternalFieldUpdaterService implements ExternalFieldUpdaterServiceInterface
{
    public function __construct(
        public readonly MainResourceRepositoryInterface $mainResourceRepository,
        public readonly AttachmentRepositoryInterface $attachmentRepository,
    ) {
    }

    /**
     * @param string $externalIdName
     * @param mixed $externalValue
     * @param MainResourceInterface|AttachmentInterface $resource
     *
     * @return void
     * @throws Exception
     */
    public function updateExternalField(
        string $externalIdName,
        mixed $externalValue,
        MainResourceInterface|AttachmentInterface $resource
    ): void {
        $external = $resource->getExternalId();
        $external[$externalIdName] = $externalValue;
        $resource->setExternalId($external);

        if ($resource instanceof AttachmentInterface) {
            AttachmentModel::update([
                'set'   => ['external_id' => json_encode($resource->getExternalId())],
                'where' => ['res_id = ?'],
                'data'  => [$resource->getResId()]
            ]);
        } else {
            ResModel::update([
                'set'   => ['external_id' => json_encode($resource->getExternalId())],
                'where' => ['res_id = ?'],
                'data'  => [$resource->getResId()]
            ]);
        }
    }
}
