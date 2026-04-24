<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Config Not Found Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagConfigNotFoundProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _GOODFLAG_CONFIG_NOT_FOUND_,
            400,
            lang: 'missingGoodflagConfig'
        );
    }
}
