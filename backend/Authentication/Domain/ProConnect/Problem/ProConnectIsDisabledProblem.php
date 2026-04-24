<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ProConnect Is Disabled Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Domain\ProConnect\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ProConnectIsDisabledProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _PROCONNECT_IS_DISABLED_,
            400,
            lang: 'proConnectIsDisabled'
        );
    }
}
