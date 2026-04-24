<?php

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CannotGetServiceVersionProblem extends Problem
{
    public function __construct(string $reason)
    {
        parent::__construct(
            _CANNOT_GET_SIGNATORY_BOOK_VERSION,
            400,
            context: [
                'reason' => $reason
            ]
        );
    }
}
