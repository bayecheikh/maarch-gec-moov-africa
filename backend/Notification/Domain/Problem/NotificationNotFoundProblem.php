<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Not Found Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class NotificationNotFoundProblem extends Problem
{
    public function __construct(string $notificationId)
    {
        parent::__construct(
            _NOTIFICATION_NOT_FOUND . " : $notificationId",
            400,
            ['notificationId' => $notificationId],
            'notificationNotFound'
        );
    }
}
