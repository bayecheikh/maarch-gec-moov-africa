<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief UpdateDiffusionExceptRecipientProcess Privilege
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DiffusionList\Domain\Privilege;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;

class UpdateDiffusionExceptRecipientProcessPrivilege implements PrivilegeInterface
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'update_diffusion_except_recipient_process';
    }
}
