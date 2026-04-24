<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Cannot Update Resource Content In Signatory Book Problem
 * @author dev@maarch.org
 */


namespace MaarchCourrier\SignatureBook\Domain\Problem\Resource;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CannotUpdateResourceContentInSignatoryBookProblem extends Problem
{
    public function __construct(string $detail)
    {
        parent::__construct(
            _UPDATE_RESOURCE_SIGNATORY_BOOK_ERROR . " : $detail",
            400,
            [
                'error' => $detail
            ]
        );
    }
}
