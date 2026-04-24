<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Retrieve Document Workflow class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application;

use MaarchCourrier\Core\Domain\Attachment\AttachmentNotFoundProblem;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourcePerimeterCheckerInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\Problem\ParameterStringMustBeOfValueProblem;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\CommonExternalSignatureBookApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Problem\ResourceIsNotLinkedToAnExternalSignatureBookProblem;
use MaarchCourrier\MainResource\Domain\Problem\MainResourceDoesNotExistProblem;

class RetrieveDocumentWorkflow
{
    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly MainResourcePerimeterCheckerInterface $mainResourcePerimeterChecker,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly CommonExternalSignatureBookApiServiceInterface $commonApiService
    ) {
    }

    /**
     * @param string $type
     * @param int    $id
     *
     * @return array
     * @throws AttachmentNotFoundProblem
     * @throws MainResourceDoesNotExistProblem
     * @throws ParameterStringMustBeOfValueProblem
     * @throws ResourceIsNotLinkedToAnExternalSignatureBookProblem
     */
    public function getByTypeAndId(string $type, int $id): array
    {
        if (empty($type) || !in_array($type, ['resource', 'attachment'])) {
            throw new ParameterStringMustBeOfValueProblem('type', 'resource | attachment');
        }

        if ($type == 'resource') {
            $check = $this->mainResourcePerimeterChecker->hasRightByResId($id, $this->currentUser->getCurrentUser());
            $resource = $this->mainResourceRepository->getMainResourceByResId($id);
            if ($resource === null || !$check) {
                throw new MainResourceDoesNotExistProblem();
            }
        } else {
            $resource = $this->attachmentRepository->getAttachmentByResId($id);
            if ($resource === null) {
                throw new AttachmentNotFoundProblem($id);
            }
            $check = $this->mainResourcePerimeterChecker->hasRightByResId(
                $resource->getMainResource()->getResId(),
                $this->currentUser->getCurrentUser()
            );
            if (!$check) {
                throw new MainResourceDoesNotExistProblem();
            }
        }

        if (empty($resource->getExternalId()['signatureBookId'] ?? null)) {
            throw new ResourceIsNotLinkedToAnExternalSignatureBookProblem(
                $resource,
                $this->commonApiService->getName()
            );
        }

        // Depending on the external signature book, fetch the document workflow differently
        $this->commonApiService->loadConfig();
        return $this->commonApiService->fetchDocumentWorkflow($resource);
    }
}
