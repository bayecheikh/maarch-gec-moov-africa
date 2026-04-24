<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Convert One Page From File Content Problem class
 * @author dev@maarch.org
 */
namespace MaarchCourrier\DocumentConversion\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ConvertOnePageFromFileContentProblem extends Problem
{
    public function __construct(string $fileExtensionFrom, string $fileExtensionTo, string $detail)
    {
        parent::__construct(
            _COULD_NOT_CONVERT_ERROR . " $fileExtensionFrom to $fileExtensionTo : $detail",
            400,
            [
                'fileExtensionFrom' => $fileExtensionFrom,
                'fileExtensionTo' => $fileExtensionTo
            ]
        );
    }
}
