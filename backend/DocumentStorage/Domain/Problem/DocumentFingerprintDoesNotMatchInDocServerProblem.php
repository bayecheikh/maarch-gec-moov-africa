<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Document Fingerprint Does Not Match In Doc Server Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class DocumentFingerprintDoesNotMatchInDocServerProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _FINGERPRINT_DOESNT_MATCH_DOCSERVER_ERROR,
            400
        );
    }
}
