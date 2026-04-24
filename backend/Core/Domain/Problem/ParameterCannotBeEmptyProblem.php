<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Parameter Cannot Be Empty Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class ParameterCannotBeEmptyProblem extends Problem
{
    public function __construct(string $parameterName)
    {
        parent::__construct(_PARAMETER . " '$parameterName' " . _CANNOT_BE_EMPTY, 400);
    }
}
