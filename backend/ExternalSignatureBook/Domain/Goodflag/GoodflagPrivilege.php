<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Goodflag Privilege class
* @author dev@maarch.org
*/

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;

class GoodflagPrivilege implements PrivilegeInterface
{
    public function getName(): string
    {
        return 'admin_goodflag';
    }
}
