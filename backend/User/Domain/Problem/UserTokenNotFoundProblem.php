<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief UserTokenNotFoundProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class UserTokenNotFoundProblem extends Problem
{
    public function __construct(?int $userId = null)
    {
        parent::__construct(
            _USER_TOKEN_NOT_FOUND_ERROR . " : " . $userId,
            400,
            [
                "userId" => $userId
            ]
        );
    }
}
