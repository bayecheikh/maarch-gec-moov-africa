<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Invalid Numeric Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class InvalidNumericProblem extends Problem
{
    public function __construct(string $parameterName)
    {
        parent::__construct(
            _INVALID_NUMERIC_VALUE . " : $parameterName",
            400,
            ['parameterName' => $parameterName]
        );
    }
}
