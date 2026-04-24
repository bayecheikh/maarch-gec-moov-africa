<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Api Token Notification Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Domain\Port;

interface ApiTokenScheduleServiceInterface
{
    public function createNotifAlert(): void;

    public function doesNotifAlert(): bool;

    public function deleteNotifAlert(): void;
}
