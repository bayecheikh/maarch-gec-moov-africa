<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Nonce Parameter Is Empty Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Domain\ProConnect\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class NonceParameterIsEmptyProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _NONCE_IS_EMPTY,
            400,
            lang: 'nonceIsEmpty',
        );
    }
}
