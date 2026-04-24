<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature is Mandatory Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class SignatureIsMandatoryProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _SIGNATURE_MANDATORY_FOR_SIGNATORY,
            400,
            [],
            'signatureIsMandatory'
        );
    }
}
