<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief File System Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain\Port;


interface FileSystemServiceInterface
{
    public function getFileMimeTypeByContent(string $fileContent): false|string;

    public function doesFolderExist(string $folderPath): bool;

    public function doesFileExist(string $filePath): bool;

    public function getFileContent(string $filePath): false|string;

    public function getFingerPrint(string $filePath, string $mode = ''): string;

    public function getNumberOfPagesFromPdfFile(string $filePath): int;
}
