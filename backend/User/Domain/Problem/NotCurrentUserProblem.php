<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief No Current User Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class NotCurrentUserProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _CURRENT_USER_AND_SIGNATORY_DIFFERENT_ERROR,
            400
        );
    }
}
