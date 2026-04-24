<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Does Not Exist Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class UserDoesNotExistProblem extends Problem
{
    public function __construct(?int $userId = null)
    {
        $message = empty($userId) ? _USER . " " . _NOT_EXISTS : _USER . " '$userId' " . _NOT_EXISTS;
        parent::__construct(
            $message,
            400,
            [
                'userId' => $userId
            ]
        );
    }
}
