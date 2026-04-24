<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Signature Profile Invalid Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagSignatureProfileInvalidProblem extends Problem
{
    public function __construct(string $signatureProfileId)
    {
        parent::__construct(
            _GOODFLAG_SIGNATURE_PROFILE_ID_NOT_FOUND . " : $signatureProfileId",
            400,
            [
                'signatureProfileId' => $signatureProfileId
            ],
            'goodflagSignatureProfileIdNotFound'
        );
    }
}
