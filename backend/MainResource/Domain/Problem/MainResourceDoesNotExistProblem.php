<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Does Not Exist Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\MainResource\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MainResourceDoesNotExistProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(_MAIN_RESOURCE_DOESNT_EXIST_ERROR, 400);
    }
}
