<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Reset Workflow In Signature Book Action
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Action;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\SignatureBook\Domain\Port\Link\SignatureBookLinkServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\Workflow\SignatureBookWorkflowServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Workflow\NoWorkflowDefinedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Workflow\WorkflowHasEndedProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;

class ResetWorkflowInSignatureBookAction
{
    /**
     * @param EnvironmentInterface $environment
     * @param SignatureServiceConfigLoaderInterface $signatureServiceJsonConfigLoader
     * @param SignatureBookWorkflowServiceInterface $signatureBookWorkflowService
     * @param VisaWorkflowRepositoryInterface $visaWorkflowRepository
     * @param MainResourceRepositoryInterface $mainResourceRepository
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param SignatureBookLinkServiceInterface $signatureBookLinkService
     */
    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceJsonConfigLoader,
        private readonly SignatureBookWorkflowServiceInterface $signatureBookWorkflowService,
        private readonly VisaWorkflowRepositoryInterface $visaWorkflowRepository,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly SignatureBookLinkServiceInterface $signatureBookLinkService
    ) {
    }

    /**
     * @param int $resourceId
     *
     * @return void
     * @throws NoWorkflowDefinedProblem
     * @throws ResourceDoesNotExistProblem
     * @throws SignatureBookNoConfigFoundProblem
     * @throws WorkflowHasEndedProblem
     */
    public function reset(
        int $resourceId
    ): void {
        $mainResource = $this->mainResourceRepository->getMainResourceByResId($resourceId);
        if ($mainResource === null) {
            throw new ResourceDoesNotExistProblem();
        }

        if (!$this->visaWorkflowRepository->isInWorkflow($mainResource)) {
            if (!empty($this->visaWorkflowRepository->hasWorkflow($mainResource))) {
                throw new WorkflowHasEndedProblem();
            } else {
                throw new NoWorkflowDefinedProblem();
            }
        }

        if ($this->environment->isNewInternalParapheurEnabled()) {
            $signatureBook = $this->signatureServiceJsonConfigLoader->getSignatureServiceConfig();
            if ($signatureBook === null) {
                throw new SignatureBookNoConfigFoundProblem();
            }
            $this->signatureBookWorkflowService->setConfig($signatureBook);

            $mainResourceToSing = [];
            if ($mainResource->getExternalDocumentId() !== null) {
                $mainResourceToSing = [SignatureBookResource::createFromMainResource($mainResource)];
            }
            $attachments = $this->attachmentRepository->getAttachmentsWithAnInternalParapheur($mainResource);

            /**
             * @var SignatureBookResource[] $signatureBookResource
             */
            $signatureBookResource = array_merge(
                $mainResourceToSing,
                SignatureBookResource::createFromAttachments($attachments)
            );

            foreach ($signatureBookResource as $resource) {
                if (
                    !$resource->getResource()->getHasDigitalSignature() &&
                    !empty($resource->getExternalDocumentId())
                ) {
                    $this->signatureBookLinkService->unlinkResources($resource);
                }
            }
        }

        $this->visaWorkflowRepository->restWorkflowByMainResource($mainResource);
    }
}
