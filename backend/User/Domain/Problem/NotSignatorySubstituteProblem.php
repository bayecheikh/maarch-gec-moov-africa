<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Not Signatory Substitute Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class NotSignatorySubstituteProblem extends Problem
{
    public function __construct(array $context)
    {
        parent::__construct(
            $context["signatorySubstitute"] . _IS_NOT_SUBSTITUTE_OF_ERROR . $context["ownerSignatory"],
            400,
            [
                "signatorySubstitute" => $context["signatorySubstitute"],
                "ownerSignatory"      => $context["ownerSignatory"],
            ]
        );
    }
}
