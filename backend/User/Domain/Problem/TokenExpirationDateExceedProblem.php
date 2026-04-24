<?php

namespace MaarchCourrier\User\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class TokenExpirationDateExceedProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _TOKEN_DURATION_TOO_LONG_ERROR,
            400
        );
    }
}
