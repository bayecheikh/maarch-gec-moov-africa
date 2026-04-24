<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Missing Configuration Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class IxbusMissingAttributeConfigProblem extends Problem
{
    public function __construct(string $paramName, ?int $position = null)
    {
        $info = "Missing attribute '$paramName' in signature book configuration.";
        if ($position !== null) {
            $info = "At position $position. $info";
        }

        parent::__construct(
            "Ixbus: $info",
            400
        );
    }
}
