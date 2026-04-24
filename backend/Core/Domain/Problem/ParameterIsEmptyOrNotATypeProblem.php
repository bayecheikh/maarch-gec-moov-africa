<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Parameter Is Empty Or Not a Type Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class ParameterIsEmptyOrNotATypeProblem extends Problem
{
    public function __construct(string $parameterName, string $type)
    {
        parent::__construct(
            _PARAMETER . " '$parameterName' " . _IS_EMPTY_OR_NOT_A_TYPE . " '$type'",
            400
        );
    }
}
