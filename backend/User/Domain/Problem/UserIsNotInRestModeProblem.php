<?php

namespace MaarchCourrier\User\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class UserIsNotInRestModeProblem extends Problem
{
    public function __construct(?int $userId = null)
    {
        parent::__construct(
            _USER_NOT_REST_ERROR . " : " . $userId,
            400,
            [
                "userId" => $userId
            ]
        );
    }
}
