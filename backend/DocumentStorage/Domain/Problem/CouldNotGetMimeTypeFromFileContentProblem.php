<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Could Not Get Mime Type From File Content Problem Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CouldNotGetMimeTypeFromFileContentProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(_COULD_NOT_GET_MIMETYPE_FILE_ERROR, 400);
    }
}
