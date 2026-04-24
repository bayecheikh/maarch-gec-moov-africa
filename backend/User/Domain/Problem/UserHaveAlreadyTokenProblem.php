<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief UserHaveAlreadyTokenProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class UserHaveAlreadyTokenProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _TOKEN_ALREADY_EXIST_ERROR,
            400
        );
    }
}
