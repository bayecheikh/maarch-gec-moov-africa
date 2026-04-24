<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Cannot Unlink An Other User Mobile Information Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\MaarchMobile\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CannotUnlinkAnOtherUserMobileInformationProblem extends Problem
{
    public function __construct(int $userId)
    {
        parent::__construct(
            "Cannot unlink an other user mobile information. User id : $userId",
            401,
            [
                'userId' => $userId
            ],
            'cannotUnlinkAnOtherUserMobileInformation'
        );
    }
}
