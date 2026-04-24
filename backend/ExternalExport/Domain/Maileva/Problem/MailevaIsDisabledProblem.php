<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Is Disabled Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MailevaIsDisabledProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _DISABLED_MAILEVA_CONFIG_,
            400,
            lang: 'disabledMailevaConfig'
        );
    }
}
