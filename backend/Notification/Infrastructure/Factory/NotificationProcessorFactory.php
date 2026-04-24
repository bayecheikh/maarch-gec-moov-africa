<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Processor Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Infrastructure\Factory;

use MaarchCourrier\Basket\Infrastructure\Repository\BasketRepository;
use MaarchCourrier\Basket\Infrastructure\Service\BasketClauseService;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationProcessorFactoryInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationChannelInterface;
use MaarchCourrier\DiffusionList\Infrastructure\Repository\ListInstanceRepository;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\Group\Infrastructure\Repository\GroupRepository;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\Notification\Application\NotificationProcessor;
use MaarchCourrier\Notification\Application\NotificationRecipientFilter;
use MaarchCourrier\Notification\Infrastructure\Repository\NotificationEventRepository;
use MaarchCourrier\Notification\Infrastructure\Repository\NotificationRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use Psr\Log\LoggerInterface;

class NotificationProcessorFactory implements NotificationProcessorFactoryInterface
{
    public static function create(
        LoggerInterface $logger,
        NotificationChannelInterface $channel
    ): NotificationProcessor {
        $userRepo = new UserRepository();
        $entityRepo = new EntityRepository();

        return new NotificationProcessor(
            new NotificationRepository(),
            new BasketRepository(),
            new GroupRepository(),
            $userRepo,
            new BasketClauseService(),
            new MainResourceRepository($userRepo, new TemplateRepository(), $entityRepo),
            new NotificationEventRepository($userRepo),
            new NotificationRecipientFilter($userRepo, $entityRepo, new ListInstanceRepository($userRepo, $entityRepo)),
            $channel,
            $logger
        );
    }
}
