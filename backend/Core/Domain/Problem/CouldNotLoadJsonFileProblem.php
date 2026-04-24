<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Could Not Load Json File Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class CouldNotLoadJsonFileProblem extends Problem
{
    public function __construct(string $filePath)
    {
        parent::__construct(
            _COULD_NOT_LOAD_JSON_FILE . " : '$filePath'",
            500,
            [
                "filePath" => $filePath
            ]
        );
    }
}
