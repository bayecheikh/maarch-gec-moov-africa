<?php

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class IdParapheurIsMissingProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _ID_SIGNATORY_BOOK_MISSING,
            400
        );
    }
}
