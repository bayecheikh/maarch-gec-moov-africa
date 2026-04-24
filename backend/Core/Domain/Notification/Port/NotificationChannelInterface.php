<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Sender
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Notification\Port;

use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface NotificationChannelInterface
{
    /**
     * Get a prefix string for event_info to distinguish this sender (e.g., "Notification " or "Notification MCM ").
     */
    public function getEventInfoPrefix(): string;

    /**
     * Initialize the sender before sending notifications
     * @param BasketInterface[] $baskets
     * @returns bool True if ready, False if not
     */
    public function initialize(array $baskets): bool;

    /**
     * Prepare and send the notification for the given user and events.
     * @param NotificationInterface $notification The notification settings.
     * @param UserInterface $recipient The user who will receive the notification.
     * @param array $basketsEvents An associative array: basketId => NotificationEvent[].
     */
    public function sendNotification(
        NotificationInterface $notification,
        UserInterface $recipient,
        array $basketsEvents
    ): void;
}
