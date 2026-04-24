<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Parameter String Must Be Of Value Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class ParameterStringMustBeOfValueProblem extends Problem
{
    public function __construct(string $type, string $detail)
    {
        parent::__construct(
            _THE_STRING_TYPE_PARAMETER . " '$type' " . _MUST_HAVE_ONE_OF_THESE_VALUES . " : " . $detail,
            400
        );
    }
}
