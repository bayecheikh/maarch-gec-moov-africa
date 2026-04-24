<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Api Could Not Retrieve Consent Pages Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagApiCouldNotRetrieveConsentPagesProblem extends Problem
{
    public function __construct(string $detail, int $status)
    {
        parent::__construct(
            _GOODFLAG_API_COULD_NOT_RETRIEVE_CONSENT_PAGES_ . " : $detail",
            $status,
            [
                'error' => $detail
            ],
            'goodflagApiCouldNotRetrieveConsentPages'
        );
    }
}
