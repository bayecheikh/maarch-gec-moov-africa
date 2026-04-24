<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief RetrieveSignatureBook class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application;

use MaarchCourrier\Authorization\Domain\Problem\MainResourceOutOfPerimeterProblem;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\Authorization\Port\AttachmentPrivilegeCheckerServiceInterface;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourcePerimeterCheckerInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\DocumentConversion\Domain\Port\ConvertPdfServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Privilege\SignDocumentPrivilege;
use MaarchCourrier\SignatureBook\Domain\Problem\MainResourceDoesNotExistInSignatureBookBasketProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBook;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;

class RetrieveSignatureBook
{
    /**
     * @param  MainResourceRepositoryInterface  $mainResourceRepository
     * @param  CurrentUserInterface  $currentUser
     * @param  MainResourcePerimeterCheckerInterface  $mainResourceAccessControl
     * @param  SignatureBookRepositoryInterface  $signatureBookRepository
     * @param  ConvertPdfServiceInterface  $convertPdfService
     * @param  AttachmentRepositoryInterface  $attachmentRepository
     * @param  PrivilegeCheckerInterface  $privilegeChecker
     * @param  VisaWorkflowRepositoryInterface  $visaWorkflowRepository
     * @param  AttachmentPrivilegeCheckerServiceInterface  $attachmentPrivilegeCheckerService
     */
    public function __construct(
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly CurrentUserInterface $currentUser,
        private readonly MainResourcePerimeterCheckerInterface $mainResourceAccessControl,
        private readonly SignatureBookRepositoryInterface $signatureBookRepository,
        private readonly ConvertPdfServiceInterface $convertPdfService,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly PrivilegeCheckerInterface $privilegeChecker,
        private readonly VisaWorkflowRepositoryInterface $visaWorkflowRepository,
        private readonly AttachmentPrivilegeCheckerServiceInterface $attachmentPrivilegeCheckerService
    ) {
    }

    /**
     * @param int $resId
     * @return SignatureBook
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function getSignatureBook(int $resId, int $groupId, int $basketId): SignatureBook
    {
        $resource = $this->mainResourceRepository->getMainResourceByResId($resId);
        if ($resource === null) {
            throw new ResourceDoesNotExistProblem();
        }

        $currentUser = $this->currentUser->getCurrentUser();
        if (!$this->mainResourceAccessControl->hasRightByResId($resource->getResId(), $currentUser)) {
            throw new MainResourceOutOfPerimeterProblem();
        }

        $isInSignatureBook = $this->signatureBookRepository
            ->isMainResourceInSignatureBookBasket($resource, $currentUser);
        if (empty($isInSignatureBook)) {
            throw new MainResourceDoesNotExistInSignatureBookBasketProblem();
        }

        $basketPrivileges = $this->signatureBookRepository->retrieveSignatureBookPrivileges($groupId, $basketId);
        $canAddAttachments = $basketPrivileges['canAddDocumentInSignatureBook'];
        $canUpdateDocuments = $basketPrivileges['canUpdateRemoveDocumentInSignatureBook'];

        $resourcesToSign = [];
        $resourcesAttached = [];

        $mainSignatureBookResource = SignatureBookResource::createFromMainResource($resource);

        $mainSignatureBookResource->setIsAnnotated($resource->isAnnotated());

        if (!empty($resource->getFilename())) {
            $isConverted = $this->convertPdfService->canConvert($resource->getFileFormat());
            $mainSignatureBookResource->setIsConverted($isConverted);

            if ($resource->isInSignatureBook()) {
                $resourcesToSign[] = $mainSignatureBookResource;
            } else {
                $isCreator = $resource->getTypist()->getId() == $this->currentUser->getCurrentUser()->getId();
                $canModify = $canUpdateDocuments || $isCreator;

                $mainSignatureBookResource->setCanModify($canModify);
                $resourcesAttached[] = $mainSignatureBookResource;
            }
        }

        $attachments = $this->attachmentRepository->getAttachmentsInSignatureBookByMainResource($resource);
        foreach ($attachments as $attachment) {
            $isConverted = $this->convertPdfService->canConvert($attachment->getFileFormat());
            $isAnnotated = $attachment->isAnnotated();
            $canUpdate = $canUpdateDocuments ||
                $this->attachmentPrivilegeCheckerService->canUpdateAttachment($attachment);
            $canDelete = $canUpdateDocuments ||
                $this->attachmentPrivilegeCheckerService->canDeleteAttachment($attachment);

            $signatureBookResource = SignatureBookResource::createFromAttachment($attachment)
                ->setIsConverted($isConverted)
                ->setIsAnnotated($isAnnotated)
                ->setCanModify($canUpdate)
                ->setCanDelete($canDelete);

            if ($attachment->isSignable()) {
                $resourcesToSign[] = $signatureBookResource;
            } else {
                $resourcesAttached[] = $signatureBookResource;
            }
        }

        $canSignResources = $this->privilegeChecker->hasPrivilege($currentUser, new SignDocumentPrivilege());
        $hasActiveWorkflow = $this->visaWorkflowRepository->isWorkflowActiveByMainResource($resource);

        $isCurrentUserWorkflow = false;
        $currentWorkflowUser = $this->visaWorkflowRepository->getCurrentStepUserByMainResource($resource);
        if (!empty($currentWorkflowUser)) {
            $isCurrentUserWorkflow = $currentWorkflowUser->getId() === $currentUser->getId();
        }

        $currentStepWorkflow = $this->visaWorkflowRepository->getCurrentStepByMainResource($resource);

        $signatureBook = new SignatureBook();
        $signatureBook->setResourcesToSign($resourcesToSign)
            ->setResourcesAttached($resourcesAttached)
            ->setCanSignResources($canSignResources)
            ->setCanAddAttachments($canAddAttachments)
            ->setCanUpdateResources($canUpdateDocuments)
            ->setHasActiveWorkflow($hasActiveWorkflow)
            ->setIsCurrentWorkflowUser($isCurrentUserWorkflow)
            ->setCurrentWorkflowRole(($currentStepWorkflow) ? $currentStepWorkflow->getItemMode() : null);

        return $signatureBook;
    }
}
