<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Check Webhook Event class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;

class GoodflagCheckWebhookEvent
{
    public function __construct(
        private readonly GoodflagApiServiceInterface $goodflagApiService
    ) {
    }

    /**
     * @param string $webhookEventId
     * @return bool
     */
    public function isValid(string $webhookEventId): bool
    {
        $this->goodflagApiService->loadConfig();

        $this->goodflagApiService->setCurrentUserInformations();

        $webhookEvent = $this->goodflagApiService->retrieveWebhookEvent($webhookEventId);
        if (empty($webhookEvent)) {
            return false;
        }

        // 300 seconds = 5 minutes
        $currentTimestamp = time();
        $webhookTimestamp = $webhookEvent['updated'];
        if ($currentTimestamp - $webhookTimestamp > 300) {
            return false;
        }

        return true;
    }
}
