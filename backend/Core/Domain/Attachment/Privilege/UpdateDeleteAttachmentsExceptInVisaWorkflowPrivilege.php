<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Update and Delete Attachments Except In Visa Workflow privilege
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Attachment\Privilege;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;

class UpdateDeleteAttachmentsExceptInVisaWorkflowPrivilege implements PrivilegeInterface
{
    public function getName(): string
    {
        return 'update_delete_attachments_except_in_visa_workflow';
    }
}
