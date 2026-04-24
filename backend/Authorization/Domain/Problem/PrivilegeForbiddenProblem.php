<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Privilege Forbidden Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authorization\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class PrivilegeForbiddenProblem extends Problem
{
    public function __construct(string $privilege)
    {
        parent::__construct(
            _PRIVILEGE_FORBIDDEN . ' : ' . $privilege,
            403,
            [
                "privilege" => $privilege
            ]
        );
    }
}
