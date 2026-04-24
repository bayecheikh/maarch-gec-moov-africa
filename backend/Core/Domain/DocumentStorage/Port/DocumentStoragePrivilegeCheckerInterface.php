<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Document Storage Privilege Checker Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\DocumentStorage\Port;

interface DocumentStoragePrivilegeCheckerInterface
{
    public function isFileAllowed(string $extension, string $mimeType): bool;
}
