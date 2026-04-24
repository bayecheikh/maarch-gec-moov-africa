<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief No Unique User Mail Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Domain\ProConnect\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class NoUniqueUserMailProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _NO_UNIQUE_USER_MAIL,
            400,
            lang: 'noUniqueUserMail'
        );
    }
}
