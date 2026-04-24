<?php

namespace MaarchCourrier\Core\Domain\Authorization\Port;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;

interface AttachmentPrivilegeCheckerServiceInterface
{
    public function canUpdateAttachment(AttachmentInterface $attachment): bool;
    public function canDeleteAttachment(AttachmentInterface $attachment): bool;
}
