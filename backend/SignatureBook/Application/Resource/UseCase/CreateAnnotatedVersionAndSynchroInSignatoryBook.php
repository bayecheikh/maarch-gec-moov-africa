<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Create Annotated Version And Synchro In SignatoryBook
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Resource\UseCase;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\SignatureBook\Application\Resource\CreateAnnotatedResourceVersion;
use MaarchCourrier\SignatureBook\Application\Resource\UpdateResourceContentInSignatoryBook;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Resource\CannotCreateNewResourceVersionProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Resource\CannotUpdateResourceContentInSignatoryBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;

class CreateAnnotatedVersionAndSynchroInSignatoryBook
{
    public function __construct(
        private readonly CreateAnnotatedResourceVersion $createAnnotatedResourceVersion,
        private readonly UpdateResourceContentInSignatoryBook $updateResourceContentInSignatoryBook
    ) {
    }

    /**
     * @param int $resId
     * @param string $encodedContent
     * @param bool $isAnnotated
     * @param bool $isAttachment
     * @param bool $synchroActivated
     * @return array
     * @throws CannotCreateNewResourceVersionProblem
     * @throws CannotUpdateResourceContentInSignatoryBookProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceDoesNotExistProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function execute(
        int $resId,
        string $encodedContent,
        bool $isAnnotated = false,
        bool $isAttachment = false,
        bool $synchroActivated = true
    ): array {
        $isUpdatedResource = false;
        $resource = $this->createAnnotatedResourceVersion->execute(
            $resId,
            $encodedContent,
            $isAnnotated,
            $isAttachment
        );
        if (
            $synchroActivated &&
            (($resource instanceof MainResourceInterface && !empty($resource->getExternalDocumentId())) ||
                ($resource instanceof AttachmentInterface && !empty($resource->getExternalDocumentId())))
        ) {
            $this->updateResourceContentInSignatoryBook->execute($resource, $encodedContent);
            $isUpdatedResource = true;
        }

        return [
            'resId'             => $resource->getResId(),
            'isUpdatedResource' => $isUpdatedResource
        ];
    }
}
