<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief External Signature Book Configuration File Missing Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ExternalSignatureBookConfigurationFileMissingOrEmptyProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _SIGNATORY_BOOK_CONFIG_MISSING_ERROR,
            404
        );
    }
}
