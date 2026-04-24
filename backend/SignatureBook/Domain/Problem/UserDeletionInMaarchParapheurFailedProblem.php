<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Deletion In Maarch Parapheur Failed Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class UserDeletionInMaarchParapheurFailedProblem extends Problem
{
    /**
     * @param array $content
     */
    public function __construct(array $content)
    {
        parent::__construct(
            _DELETE_USER_SIGNATORY_BOOK_FAILED . " : " . $content['errors'],
            403,
            [
                'errors' => $content['errors']
            ],
            'deleteUserInSignatoryBookFailed'
        );
    }
}
