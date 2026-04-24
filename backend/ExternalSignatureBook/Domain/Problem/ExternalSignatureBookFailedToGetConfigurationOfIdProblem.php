<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief External Signature Book Failed To Get Configuration Of Id class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ExternalSignatureBookFailedToGetConfigurationOfIdProblem extends Problem
{
    public function __construct(string $id)
    {
        parent::__construct(
            _FAILED_GET_SIGNATORY_BOOK_FOR_ID_ERROR . " : '$id'",
            404,
            [
                'id' => $id
            ]
        );
    }
}
