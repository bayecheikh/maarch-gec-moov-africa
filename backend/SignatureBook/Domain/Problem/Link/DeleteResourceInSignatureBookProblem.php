<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Delete Resource In Signature Book Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem\Link;

use MaarchCourrier\Core\Domain\Problem\Problem;

class DeleteResourceInSignatureBookProblem extends Problem
{
    public function __construct(string $detail)
    {
        parent::__construct(
            _DELETE_RESOURCE_SIGNATORY_BOOK_ERROR . " : $detail",
            400,
            [
                'error' => $detail
            ]
        );
    }
}
