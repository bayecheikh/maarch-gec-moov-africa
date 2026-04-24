<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Doc Server Type Does Not Exist Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class DocServerTypeDoesNotExistProblem extends Problem
{
    public function __construct(string $docServerTypeId)
    {
        parent::__construct(
            _DOCSERVER_TYPE_DOESNT_EXIST_ERROR . " : '$docServerTypeId'",
            400,
            ['docServerTypeId' => $docServerTypeId]
        );
    }
}
