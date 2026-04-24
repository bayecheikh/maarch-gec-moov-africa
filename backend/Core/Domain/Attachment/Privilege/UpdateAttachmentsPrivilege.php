<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Update Attachments privilege
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Attachment\Privilege;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;

class UpdateAttachmentsPrivilege implements PrivilegeInterface
{
    public function getName(): string
    {
        return 'update_attachments';
    }
}
