<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Parameter String Can Not Be Empty Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class ParameterStringCanNotBeEmptyProblem extends Problem
{
    public function __construct(string $parameterName, array $context = [])
    {
        parent::__construct(_PARAMETER_STRING . " '$parameterName' " . _CANNOT_BE_EMPTY, 403, $context);
    }
}
