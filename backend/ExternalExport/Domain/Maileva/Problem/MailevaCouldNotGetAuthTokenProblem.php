<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Could Not Fetch Auth Token Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MailevaCouldNotGetAuthTokenProblem extends Problem
{
    public function __construct(string $detail, int $code = 400)
    {
        parent::__construct(
            _COULD_NOT_GET_AUTH_TOKEN_FROM_MAILEVA_ . " : $detail",
            $code,
            ['error' => $detail],
            'couldNotGetAuthToken'
        );
    }
}
