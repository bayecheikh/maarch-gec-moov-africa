<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Api Could Not Retrieve Webhook Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagApiCouldNotRetrieveWebhookProblem extends Problem
{
    public function __construct(string $detail, int $status)
    {
        parent::__construct(
            _GOODFLAG_API_COULD_NOT_RETRIEVE_WEBHOOK_ . " : $detail",
            $status,
            [
                'error' => $detail
            ],
            'goodflagApiCouldNotRetrieveWebhook'
        );
    }
}
