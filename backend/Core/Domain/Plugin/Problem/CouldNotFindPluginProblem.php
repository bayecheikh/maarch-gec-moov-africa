<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Could Not Find Plugin Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Plugin\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CouldNotFindPluginProblem extends Problem
{
    public function __construct(string $name)
    {
        parent::__construct(
            _PLUGIN . " " . _NOT_FOUND . " : '$name'.",
            404,
            [
                "pluginName" => $name
            ]
        );
    }
}
