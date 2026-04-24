<?php

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class DocumentIsNotSignedProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _DOCUMENT_NOT_SIGNED_ERROR,
            400
        );
    }
}
