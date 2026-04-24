<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Api Could Add Recipient Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MailevaApiCouldNotAddRecipientProblem extends Problem
{
    public function __construct(string $detail, int $status)
    {
        parent::__construct(
            _MAILEVA_API_COULD_NOT_ADD_RECIPIENT_ . " : $detail",
            $status,
            [
                'error' => $detail
            ],
            'mailevaApiCouldNotAddRecipientProblem'
        );
    }
}
