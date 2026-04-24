<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Natures class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class IxbusInstanceIdIsNotDefinedProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            "Ixbus instance is not defined",
            400
        );
    }
}
