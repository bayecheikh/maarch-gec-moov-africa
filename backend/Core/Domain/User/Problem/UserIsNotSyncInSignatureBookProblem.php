<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Is Not Sync In Signature Book Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class UserIsNotSyncInSignatureBookProblem extends Problem
{
    public function __construct(int $userId)
    {
        parent::__construct(
            _USER_NOT_SYNCHRONIZED_ERROR . " : " . $userId,
            400,
            [
                'userId' => $userId
            ]
        );
    }
}
