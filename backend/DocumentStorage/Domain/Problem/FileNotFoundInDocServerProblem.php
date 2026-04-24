<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief File Not Found In Doc Server Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class FileNotFoundInDocServerProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _FILE_NOT_FOUND_DOCSERVER_ERROR,
            400
        );
    }
}
