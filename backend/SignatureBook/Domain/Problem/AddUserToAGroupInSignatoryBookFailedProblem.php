<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Add User To A Group In Signatory Book Failed Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class AddUserToAGroupInSignatoryBookFailedProblem extends Problem
{
    public function __construct(array $content)
    {
        parent::__construct(
            _ADD_USER_TO_GROUP_SIGNATORY_BOOK_FAILED . " :  " . $content["errors"],
            500,
            [
                'errors' => $content["errors"]
            ],
            'AddUserToAGroupInSignatoryBookFailed'
        );
    }
}
