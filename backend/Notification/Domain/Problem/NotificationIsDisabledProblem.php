<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Is Disabled Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Domain\Problem;

use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;

class NotificationIsDisabledProblem extends Problem
{
    public function __construct(NotificationInterface $notification)
    {
        parent::__construct(
            _NOTIFICATION_IS_DISABLED . " : {$notification->getStringId()}",
            400,
            [
                'id'                      => $notification->getId(),
                'notificationId'          => $notification->getStringId(),
                'notificationDescription' => $notification->getDescription()
            ],
            'notificationIsDisabled'
        );
    }
}
