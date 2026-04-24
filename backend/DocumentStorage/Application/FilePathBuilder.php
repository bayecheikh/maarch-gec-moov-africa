<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief File Path Builder
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Application;

use MaarchCourrier\DocumentStorage\Domain\Document;
use MaarchCourrier\DocumentStorage\Domain\Port\DocServerRepositoryInterface;
use MaarchCourrier\DocumentStorage\Domain\Port\FileSystemServiceInterface;
use MaarchCourrier\DocumentStorage\Domain\Problem\DocServerDoesNotExistProblem;
use MaarchCourrier\DocumentStorage\Domain\Problem\DocumentFingerprintDoesNotMatchInDocServerProblem;
use MaarchCourrier\DocumentStorage\Domain\Problem\FileNotFoundInDocServerProblem;

class FilePathBuilder
{
    public function __construct(
        private readonly DocServerRepositoryInterface $docServerRepository,
        private readonly FileSystemServiceInterface $fileSystemService
    ) {
    }

    /**
     * @param Document $document
     *
     * @return string
     * @throws DocServerDoesNotExistProblem
     * @throws DocumentFingerprintDoesNotMatchInDocServerProblem
     * @throws FileNotFoundInDocServerProblem
     */
    public function build(Document $document): string
    {
        $docServer = $this->docServerRepository->getByStorageZoneId($document->getDocserverId());
        if ($docServer == null || !$this->fileSystemService->doesFolderExist($docServer->getPathTemplate())) {
            throw new DocServerDoesNotExistProblem($document->getDocserverId());
        }

        $filePath = $docServer->getPathTemplate() .
            str_replace('#', DIRECTORY_SEPARATOR, $document->getPath()) . $document->getFileName();

        if (!$this->fileSystemService->doesFileExist($filePath)) {
            throw new FileNotFoundInDocServerProblem();
        }

        if (
            $document->getFingerprint() !=
            $this->fileSystemService->getFingerPrint($filePath, $docServer->getDocserverType()->getFingerprintMode())
        ) {
            throw new DocumentFingerPrintDoesNotMatchInDocServerProblem();
        }

        return $filePath;
    }
}
