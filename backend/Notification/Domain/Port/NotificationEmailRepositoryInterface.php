<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Email Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Domain\Port;

interface NotificationEmailRepositoryInterface
{
    public function insert(NotificationEmailInterface $notificationEmail): void;

    /**
     * @return NotificationEmailInterface[]
     */
    public function getUnExecutedEmails(): array;

    public function setExecutionDate(NotificationEmailInterface $email, ?string $status = null): void;
}
