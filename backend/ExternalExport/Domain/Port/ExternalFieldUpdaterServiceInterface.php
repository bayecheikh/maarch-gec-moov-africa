<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief External Export Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Port;

use Exception;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;

interface ExternalFieldUpdaterServiceInterface
{
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
    ): void;
}
