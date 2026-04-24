<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment File Retriever
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Attachment\Application;

use MaarchCourrier\Core\Domain\Attachment\AttachmentNotFoundProblem;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\DocumentConversion\Domain\Port\ConvertPdfServiceInterface;
use MaarchCourrier\DocumentStorage\Application\FilePathBuilder;
use MaarchCourrier\DocumentStorage\Domain\Problem\DocServerDoesNotExistProblem;
use MaarchCourrier\DocumentStorage\Domain\Problem\DocumentFingerprintDoesNotMatchInDocServerProblem;
use MaarchCourrier\DocumentStorage\Domain\Problem\FileNotFoundInDocServerProblem;

class AttachmentFileRetriever
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly ConvertPdfServiceInterface $convertPdfService,
        private readonly FilePathBuilder $filePathBuilder
    ) {
    }

    /**
     * @param int $id
     * @param bool $isOriginal
     *
     * @return array
     * @throws AttachmentNotFoundProblem
     * @throws DocServerDoesNotExistProblem
     * @throws DocumentFingerprintDoesNotMatchInDocServerProblem
     * @throws FileNotFoundInDocServerProblem
     */
    public function getById(int $id, bool $isOriginal = true): array
    {
        $attachment = $this->attachmentRepository->getAttachmentByResId($id);
        if ($attachment === null) {
            throw new AttachmentNotFoundProblem($id);
        }

        if (!$isOriginal) {
            if ($attachment->getStatus() === 'SIGN') {
                $signedAttachment = $this->attachmentRepository->getSignAttachmentByOriginId(
                    $attachment->getResId()
                );
                if ($signedAttachment !== null) {
                    $attachment = $signedAttachment;
                }
            }
            // convert
            $convertedDocument = $this->convertPdfService->getAttachmentPdfById($attachment->getResId());

            if (empty($convertedDocument['errors'] ?? null)) {
                $document = $attachment->getDocument();
                $document->setFileName($convertedDocument['filename']);
                $document->setFileExtension('pdf');
                $document->setFingerprint($convertedDocument['fingerprint']);
                $document->setPath($convertedDocument['path']);
                $document->setDocserverId($convertedDocument['docserver_id']);
                $attachment->setDocument($document);
            }
        }

        $attachmentFilePath = $this->filePathBuilder->build($attachment->getDocument());

        $fileName = (empty($attachment->getTitle()) ? 'document' : $attachment->getTitle()) .
            ".{$attachment->getDocument()->getFileExtension()}";

        return ['path' => $attachmentFilePath, 'filename' => $fileName];
    }
}
