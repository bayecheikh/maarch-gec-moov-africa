<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Basket Not Found Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Basket\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class BasketNotFoundProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(_BASKET . " " . _NOT_FOUND, 400);
    }
}
