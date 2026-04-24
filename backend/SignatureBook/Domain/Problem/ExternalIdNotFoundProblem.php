<?php

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ExternalIdNotFoundProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _DOCUMENT_NOT_LINKED_WITH_SIGNATORY_BOOK,
            400
        );
    }
}
