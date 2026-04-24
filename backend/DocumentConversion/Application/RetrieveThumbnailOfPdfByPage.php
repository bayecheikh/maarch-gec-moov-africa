<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Thumbnail Of Content By Page
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentConversion\Application;

use MaarchCourrier\Core\Domain\DocumentStorage\Port\DocumentStoragePrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\Problem\FileTypeIsNotAllowedProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterMustBeGreaterThanZeroException;
use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\DocumentConversion\Domain\ConvertedFileInfo;
use MaarchCourrier\DocumentConversion\Domain\Port\ConvertThumbnailServiceInterface;
use MaarchCourrier\DocumentConversion\Domain\Problem\ConvertOnePageFromFileContentProblem;
use MaarchCourrier\DocumentStorage\Domain\Port\FileSystemServiceInterface;
use MaarchCourrier\DocumentStorage\Domain\Problem\CouldNotGetMimeTypeFromFileContentProblem;

class RetrieveThumbnailOfPdfByPage
{
    public function __construct(
        private readonly FileSystemServiceInterface $fileSystemService,
        private readonly DocumentStoragePrivilegeCheckerInterface $documentStoragePrivilegeChecker,
        private readonly ConvertThumbnailServiceInterface $convertThumbnailService
    ) {
    }

    /**
     * @param string $base64FileContent
     * @param int $page
     *
     * @return ConvertedFileInfo
     * @throws ParameterStringCanNotBeEmptyProblem
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ConvertOnePageFromFileContentProblem
     * @throws CouldNotGetMimeTypeFromFileContentProblem
     * @throws FileTypeIsNotAllowedProblem
     */
    public function execute(string $base64FileContent, int $page): ConvertedFileInfo
    {
        if (empty($base64FileContent)) {
            throw new ParameterStringCanNotBeEmptyProblem('base64FileContent');
        }

        if ($page <= 0) {
            throw new ParameterMustBeGreaterThanZeroException('page');
        }

        $fileContent = base64_decode($base64FileContent);
        $mimeType = $this->fileSystemService->getFileMimeTypeByContent($fileContent);

        if (empty($mimeType)) {
            throw new CouldNotGetMimeTypeFromFileContentProblem();
        }
        if (
            !$this->documentStoragePrivilegeChecker->isFileAllowed('pdf', $mimeType) ||
            $mimeType != 'application/pdf'
        ) {
            throw new FileTypeIsNotAllowedProblem($mimeType);
        }

        // convert pdf to img
        $result = $this->convertThumbnailService->convertOnePageFromFileContent($fileContent, 'pdf', $page);
        if (!empty($result['errors'])) {
            throw new ConvertOnePageFromFileContentProblem('pdf', 'png', $result['errors']);
        }

        $fileContent = base64_encode($result['fileContent']);

        return (new ConvertedFileInfo())
            ->setFileContent($fileContent)
            ->setPageCount($result['pageCount'])
            ->setFormat('PNG');
    }
}
