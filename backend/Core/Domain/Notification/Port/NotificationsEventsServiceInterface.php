<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Link Token Maarch Mobile Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Notification\Port;

/**
 * TODO: Separate correctly the service logic in hexa
 */
interface NotificationsEventsServiceInterface
{
    public function fillEventStack(array $eventRecord): void;
}
