<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ExpirationDateMustBeInTheFutureProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class ExpirationDateMustBeInTheFutureProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _EXPIRATION_DATE_IN_FUTURE_ERROR,
            400
        );
    }
}
