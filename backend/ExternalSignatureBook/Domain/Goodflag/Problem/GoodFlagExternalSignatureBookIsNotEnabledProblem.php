<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Goodflag External Signature Book Is Not Enabled Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodFlagExternalSignatureBookIsNotEnabledProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _THE_EXTERNAL_SIGNATURE_BOOK_GOODFLAG_IS_NOT_ENABLED,
            503,
            lang: 'theExternalSignatureBookGoodFlagIsNotEnabled'
        );
    }
}
