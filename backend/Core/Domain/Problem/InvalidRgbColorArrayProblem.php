<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Invalid Rgb Color Array Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class InvalidRgbColorArrayProblem extends Problem
{
    public function __construct(string $parameterName = "")
    {
        $info = !empty($parameterName) ? " : From '$parameterName' parameter" : "";
        parent::__construct(
            _INVALID_RGB_COLOR_ARRAY . $info,
            400,
            lang: 'invalidRgbColorArray'
        );
    }
}
