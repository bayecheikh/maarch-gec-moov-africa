<?php

namespace MaarchCourrier\Authorization\Infrastructure;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Attachment\Privilege\UpdateAttachmentsExceptInVisaWorkflowPrivilege;
use MaarchCourrier\Core\Domain\Attachment\Privilege\UpdateAttachmentsPrivilege;
use MaarchCourrier\Core\Domain\Attachment\Privilege\UpdateDeleteAttachmentsExceptInVisaWorkflowPrivilege;
use MaarchCourrier\Core\Domain\Attachment\Privilege\UpdateDeleteAttachmentsPrivilege;
use MaarchCourrier\Core\Domain\Authorization\Port\AttachmentPrivilegeCheckerServiceInterface;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;

class AttachmentPrivilegeCheckerService implements AttachmentPrivilegeCheckerServiceInterface
{
    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly PrivilegeCheckerInterface $privilegeChecker,
        private readonly VisaWorkflowRepositoryInterface $visaWorkflowRepository
    ) {
    }

    public function canUpdateAttachment(AttachmentInterface $attachment): bool
    {
        $currentUser = $this->currentUser->getCurrentUser();
        $canUpdate = $attachment->getTypist()->getId() == $currentUser->getId();

        if (
            $this->privilegeChecker->hasPrivilege($currentUser, new UpdateAttachmentsPrivilege()) ||
            $this->privilegeChecker->hasPrivilege($currentUser, new UpdateDeleteAttachmentsPrivilege())
        ) {
            $canUpdate = true;
        } elseif (
            $this->privilegeChecker->hasPrivilege($currentUser, new UpdateAttachmentsExceptInVisaWorkflowPrivilege()) ||
            $this->privilegeChecker->hasPrivilege(
                $currentUser,
                new UpdateDeleteAttachmentsExceptInVisaWorkflowPrivilege()
            )
        ) {
            $hasActiveWorkflow = $this->visaWorkflowRepository->isWorkflowActiveByMainResource(
                $attachment->getMainResource()
            );

            if ($hasActiveWorkflow) {
                $canUpdate = false;
            } else {
                $canUpdate = true;
            }
        }

        if (in_array($attachment->getStatus(), ['SIGN', 'FRZ'])) {
            $canUpdate = false;
        }

        return $canUpdate;
    }

    public function canDeleteAttachment(AttachmentInterface $attachment): bool
    {
        $currentUser = $this->currentUser->getCurrentUser();
        $canDelete = $attachment->getTypist()->getId() == $currentUser->getId();

        if (
            $this->privilegeChecker->hasPrivilege($currentUser, new UpdateDeleteAttachmentsPrivilege())
        ) {
            $canDelete = true;
        } elseif (
            $this->privilegeChecker->hasPrivilege(
                $currentUser,
                new UpdateDeleteAttachmentsExceptInVisaWorkflowPrivilege()
            )
        ) {
            $hasActiveWorkflow = $this->visaWorkflowRepository->isWorkflowActiveByMainResource(
                $attachment->getMainResource()
            );

            if ($hasActiveWorkflow) {
                $canDelete = false;
            } else {
                $canDelete = true;
            }
        }

        if (in_array($attachment->getStatus(), ['SIGN', 'FRZ'])) {
            $canDelete = false;
        }

        return $canDelete;
    }
}
