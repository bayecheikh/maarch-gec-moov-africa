<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notifications Events Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Infrastructure\Service;

use Exception;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationsEventsServiceInterface;
use Notification\controllers\NotificationsEventsController;

/**
 * TODO: Separate correctly the service logic in hexa
 */
class NotificationsEventsService implements NotificationsEventsServiceInterface
{
    /**
     * @throws Exception
     */
    public function fillEventStack(array $eventRecord): void
    {
        NotificationsEventsController::fillEventStack([
            "eventId"   => $eventRecord['eventId'],
            "tableName" => $eventRecord['tableName'],
            "recordId"  => $eventRecord['recordId'],
            "userId"    => $eventRecord['userId'],
            "info"      => $eventRecord['info'],
        ]);
    }
}
