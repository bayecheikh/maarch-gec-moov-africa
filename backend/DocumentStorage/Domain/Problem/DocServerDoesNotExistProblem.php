<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Doc Server Does Not Exist Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class DocServerDoesNotExistProblem extends Problem
{
    public function __construct(string $storageZoneId)
    {
        parent::__construct(
            _DOCSERVER_DOESNT_EXIST_ERROR . " : '$storageZoneId'",
            400,
            [
                'docserverId' => $storageZoneId
            ]
        );
    }
}
