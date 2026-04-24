<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Event Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Notification\Port;

use MaarchCourrier\Notification\Domain\Port\NotificationEventInterface;

interface NotificationEventRepositoryInterface
{
    /**
     * @param array $eventValues
     * @return void
     */
    public function insertMultiple(array $eventValues): void;

    /**
     * @return NotificationEventInterface[]
     */
    public function getRecordByInfoAndUser(string $info, int $userIdToNotify, ?int $redirectedUserId = null): array;

    /**
     * @return NotificationEventInterface[]
     */
    public function getPendingEventsByNotification(NotificationInterface $notification): array;

    public function setExecResultForIds(string $result, array $ids): void;
}
