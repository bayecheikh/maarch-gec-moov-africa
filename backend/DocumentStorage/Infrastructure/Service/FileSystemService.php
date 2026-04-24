<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief File System Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Infrastructure\Service;

use finfo;
use Imagick;
use ImagickException;
use MaarchCourrier\DocumentStorage\Domain\Port\FileSystemServiceInterface;

class FileSystemService implements FileSystemServiceInterface
{
    public function getFileMimeTypeByContent(string $fileContent): false|string
    {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        return $fileInfo->buffer($fileContent);
    }

    public function doesFolderExist(string $folderPath): bool
    {
        if (empty($folderPath)) {
            return false;
        }
        return is_dir($folderPath);
    }

    public function doesFileExist(string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }
        return file_exists($filePath);
    }

    /**
     * @return false|string The function returns the read data or false on failure.
     */
    public function getFileContent(string $filePath): false|string
    {
        return file_get_contents($filePath);
    }

    public function getFingerPrint(string $filePath, string $mode = ''): string
    {
        if (empty($mode) || $mode == 'NONE') {
            $mode = 'sha512';
        }

        return hash_file(strtolower($mode), $filePath);
    }

    /**
     * Returns the number of pages in a PDF file.
     *
     * This function uses Imagick to count the number of pages in the given PDF file.
     * It first validates that the file exists, is a regular readable file, and has a .pdf extension.
     *
     * Note: Imagick (ImageMagick) is capable of reading PDF files. Please ensure your `policy.xml`
     * is correctly configured. More details can be found in the installation documentation.
     *
     * @param string $filePath Absolute path to the PDF file.
     *
     * @return int Number of pages in the PDF. Returns 0 if the file is invalid or unreadable.
     * @throws ImagickException If an error occurs while reading the file with Imagick.
     */
    public function getNumberOfPagesFromPdfFile(string $filePath): int
    {
        if (
            !file_exists($filePath) || !is_file($filePath) || !is_readable($filePath) ||
            pathInfo($filePath, PATHINFO_EXTENSION) !== 'pdf'
        ) {
            return 0;
        }

        $img = new Imagick();
        $img->pingImage($filePath);
        return $img->getNumberImages();
    }
}
