<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Privilege
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;

class MailevaPrivilege implements PrivilegeInterface
{
    public function getName(): string
    {
        return 'admin_shippings';
    }
}
