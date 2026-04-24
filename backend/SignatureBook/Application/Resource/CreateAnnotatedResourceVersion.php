<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Create Annotated Resource Version
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Resource;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\SignatureBook\Port\CreateVersionServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\Resource\CannotCreateNewResourceVersionProblem;

class CreateAnnotatedResourceVersion
{
    public function __construct(
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly CreateVersionServiceInterface $createVersionService
    ) {
    }

    /**
     * @throws ResourceDoesNotExistProblem
     * @throws CannotCreateNewResourceVersionProblem
     */
    public function execute(
        int $resId,
        string $encodedContent,
        bool $isAnnotated = false,
        bool $isAttachment = false
    ): MainResourceInterface|AttachmentInterface {
        if ($isAttachment) {
            $attachment = $this->attachmentRepository->getAttachmentByResId($resId);
            if ($attachment === null) {
                throw new ResourceDoesNotExistProblem();
            }
            $result = $this->createVersionService->createVersionForAttachment(
                $attachment,
                ['encodedFile' => $encodedContent, 'format' => 'pdf', 'isAnnotated' => $isAnnotated]
            );
        } else {
            $mainResource = $this->mainResourceRepository->getMainResourceByResId($resId);
            if ($mainResource === null) {
                throw new ResourceDoesNotExistProblem();
            }
            $result = $this->createVersionService->createVersionForResource(
                $mainResource,
                ['encodedFile' => $encodedContent, 'format' => 'pdf', 'isAnnotated' => $isAnnotated],
            );
        }

        if (isset($result['errors'])) {
            throw new CannotCreateNewResourceVersionProblem($result['errors']);
        }

        if ($isAttachment) {
            $newId = $result['newId'];
            $resource = $this->attachmentRepository->getAttachmentByResId($newId);
            $resource->setResId($newId);
        } else {
            $resource = $this->mainResourceRepository->getMainResourceByResId($resId);
            $resource->setResId($resId);
        }

        return $resource;
    }
}
