<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Convert From Encoded Content Problem class
 * @author dev@maarch.org
 */
namespace MaarchCourrier\DocumentConversion\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ConvertFromEncodedContentProblem extends Problem
{
    public function __construct(string $detail)
    {
        parent::__construct(_CONVERT_ENCODED_CONTENT_ERROR . " : $detail", 400);
    }
}
