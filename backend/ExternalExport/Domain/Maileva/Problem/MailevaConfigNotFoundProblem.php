<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Config Not Found class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MailevaConfigNotFoundProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _MAILEVA_CONFIG_NOT_FOUND_,
            400,
            lang: 'missingMailevaConfig'
        );
    }
}
