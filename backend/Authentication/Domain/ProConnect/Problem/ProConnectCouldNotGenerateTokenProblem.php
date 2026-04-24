<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ProConnect Could Generate Token Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Domain\ProConnect\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ProConnectCouldNotGenerateTokenProblem extends Problem
{
    public function __construct(string $detail, int $code = 400)
    {
        parent::__construct(
            _PROCONNECT_COULD_NOT_GENERATE_TOKEN . " : $detail",
            $code,
            ['error' => $detail],
            'proConnectCouldNotGenerateToken'
        );
    }
}
