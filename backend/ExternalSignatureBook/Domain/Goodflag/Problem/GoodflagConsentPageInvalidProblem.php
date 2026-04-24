<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Consent Page Invalid Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagConsentPageInvalidProblem extends Problem
{
    public function __construct(string $consentPageId)
    {
        parent::__construct(
            _GOODFLAG_CONSENT_PAGE_ID_NOT_FOUND . " : $consentPageId",
            400,
            [
                'consentPageId' => $consentPageId
            ],
            'goodflagConsentPageIdNotFound'
        );
    }
}
