<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Primary Entity Address Of Current User Is Not Filled Enough class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Contact\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class PrimaryEntityAddressOfCurrentUserIsNotFilledEnoughProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _USER_PRIMARY_ENTITY_ADDRESS_NOT_FILLED_ENOUGH,
            400,
            lang: 'userPrimaryEntityAddressNotFilledEnough'
        );
    }
}
