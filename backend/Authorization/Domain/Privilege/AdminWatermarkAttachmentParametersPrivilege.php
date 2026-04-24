<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Admin Watermark Attachment Parameters Privilege
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authorization\Domain\Privilege;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;

class AdminWatermarkAttachmentParametersPrivilege implements PrivilegeInterface
{
    public function getName(): string
    {
        return 'admin_parameters_watermark_attachment';
    }
}
