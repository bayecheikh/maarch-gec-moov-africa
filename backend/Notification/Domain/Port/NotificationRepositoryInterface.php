<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Domain\Port;

use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;

interface NotificationRepositoryInterface
{
    public function getByStringId(string $stringId): ?NotificationInterface;
}
