<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Processor Factory Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Notification\Port;

use MaarchCourrier\Notification\Application\NotificationProcessor;
use Psr\Log\LoggerInterface;

interface NotificationProcessorFactoryInterface
{
    public static function create(
        LoggerInterface $logger,
        NotificationChannelInterface $channel
    ): NotificationProcessor;
}
