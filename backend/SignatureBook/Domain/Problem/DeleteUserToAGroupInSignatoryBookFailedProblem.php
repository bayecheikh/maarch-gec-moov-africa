<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Delete User To A Group In Signatory Book Failed Problem
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class DeleteUserToAGroupInSignatoryBookFailedProblem extends Problem
{
    public function __construct(array $content)
    {
        parent::__construct(
            _DELETE_USER_TO_GROUP_SIGNATORY_BOOK_FAILED . " :  " . $content["errors"],
            500,
            [
                'errors' => $content["errors"]
            ],
            'deleteUserToAGroupInSignatoryBookFailed'
        );
    }
}
