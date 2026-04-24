<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Webhook Event Not Valid Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagWehbookEventNotValid extends Problem
{
    public function __construct(string $webhookEventId)
    {
        parent::__construct(
            _GOODFLAG_WEBHOOK_EVENT_NOT_VALID_ . " : $webhookEventId",
            400,
            [
                'webhookEventId' => $webhookEventId
            ],
            lang: 'goodflagWebhookEventNotValid'
        );
    }
}
