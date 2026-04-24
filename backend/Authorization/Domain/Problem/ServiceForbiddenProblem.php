<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Service Forbidden Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authorization\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ServiceForbiddenProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(_SERVICE_FORBIDDEN, 403, lang: 'serviceForbidden');
    }
}
