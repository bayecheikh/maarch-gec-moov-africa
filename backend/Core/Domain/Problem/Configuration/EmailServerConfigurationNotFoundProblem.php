<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Admin Email Server Configuration Not Found Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem\Configuration;

use MaarchCourrier\Core\Domain\Problem\Problem;

class EmailServerConfigurationNotFoundProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _EMAIL_SERVER_CONFIGURATION . " " . _NOT_FOUND,
            400,
            lang: 'emailServerConfigNotFound'
        );
    }
}
