<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Parameter Must Be Integer class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class ParameterMustBeInteger extends Problem
{
    public function __construct(string $parameterName)
    {
        parent::__construct(_PARAMETER_STRING . " '$parameterName' " . _MUST_BE_INTEGER, 400);
    }
}
