<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Document Storage Privilege Checker
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Infrastructure;

use Exception;
use MaarchCourrier\Core\Domain\DocumentStorage\Port\DocumentStoragePrivilegeCheckerInterface;
use Resource\controllers\StoreController;

class DocumentStoragePrivilegeChecker implements DocumentStoragePrivilegeCheckerInterface
{
    /**
     * @throws Exception
     */
    public function isFileAllowed(string $extension, string $mimeType): bool
    {
        return StoreController::isFileAllowed(['extension' => $extension, 'type' => $mimeType]);
    }
}
