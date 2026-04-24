<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Api Unable To Delete Sending Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MailevaApiUnableToDeleteSendingProblem extends Problem
{
    public function __construct(string $sendingId, string $detail, int $status)
    {
        parent::__construct(
            _MAILEVA_API_UNABLE_TO_DELETE_SENDING_ . " : $detail",
            $status,
            ['sendingId', $sendingId, 'error' => $detail],
            'mailevaApiUnableToDeleteSending'
        );
    }
}
