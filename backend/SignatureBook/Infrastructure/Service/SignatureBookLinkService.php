<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Book Link Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Service;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\Link\SignatureBookLinkServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\Workflow\SignatureBookWorkflowServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\Link\DeleteResourceInSignatureBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Link\InterruptWorkflowInSignatureBookProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;

class SignatureBookLinkService implements SignatureBookLinkServiceInterface
{
    /**
     * @param SignatureBookWorkflowServiceInterface $signatureBookWorkflowService
     * @param MainResourceRepositoryInterface $mainResourceRepository
     * @param AttachmentRepositoryInterface $attachmentRepository
     */
    public function __construct(
        private readonly SignatureBookWorkflowServiceInterface $signatureBookWorkflowService,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly AttachmentRepositoryInterface $attachmentRepository
    ) {
    }

    /**
     * @param SignatureBookResource $signatureBookResource
     *
     * @return void
     * @throws DeleteResourceInSignatureBookProblem
     * @throws InterruptWorkflowInSignatureBookProblem
     */
    public function unlinkResources(SignatureBookResource $signatureBookResource): void
    {
        // interrupt the workflow in MP API
        $result = $this->signatureBookWorkflowService->interruptWorkflow($signatureBookResource);
        if (is_array($result) && !empty($result['error'])) {
            throw new InterruptWorkflowInSignatureBookProblem($result['error']);
        }

        // delete resource in MP API
        $result = $this->signatureBookWorkflowService->deleteResource($signatureBookResource);
        if (is_array($result) && !empty($result['error'])) {
            throw new DeleteResourceInSignatureBookProblem($result['error']);
        }

        // remove link with signature book
        if ($signatureBookResource->getResource() instanceof MainResourceInterface) {
            $this->mainResourceRepository->removeSignatureBookLink($signatureBookResource->getResource());
        } else {
            $this->attachmentRepository->removeSignatureBookLink($signatureBookResource->getResource());
            $this->attachmentRepository->updateAttachment($signatureBookResource->getResource(), ['status' => 'A_TRA']);
        }
    }
}
