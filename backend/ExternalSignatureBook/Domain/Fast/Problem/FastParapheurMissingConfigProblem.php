<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Fast Parapheur Missing Config Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Fast\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class FastParapheurMissingConfigProblem extends Problem
{
    public function __construct(string $paramName)
    {
        $info = _MISSING_CONFIG . " '$paramName' " . _IN_THE_SIGNATURE_BOOK_CONFIG;

        parent::__construct(
            "Fast Parapheur: $info",
            500
        );
    }
}
