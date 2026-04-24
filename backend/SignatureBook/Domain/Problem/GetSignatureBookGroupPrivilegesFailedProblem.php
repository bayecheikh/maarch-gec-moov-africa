<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Get Maarch Parapheur Group Privileges Failed Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GetSignatureBookGroupPrivilegesFailedProblem extends Problem
{
    public function __construct(array $content)
    {
        parent::__construct(
            _FAILED_GET_GROUP_PRIVILEGES_SIGNATORY_BOOK . " :  " . $content["errors"],
            500,
            [
                'errors' => $content["errors"]
            ]
        );
    }
}
