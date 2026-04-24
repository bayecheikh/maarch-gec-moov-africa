<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ProConnect Could Not Retrieve User Infos Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Domain\ProConnect\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ProConnectCouldNotRetrieveUserInfosProblem extends Problem
{
    public function __construct(string $detail, int $code = 400)
    {
        parent::__construct(
            _PROCONNECT_COULD_NOT_RETRIEVE_USER_INFO . " : $detail",
            $code,
            ['error' => $detail],
            'proConnectCouldNotRetrieveUserInfos'
        );
    }
}
