<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Processor
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Application;

use MaarchCourrier\Core\Domain\Basket\Port\BasketClauseServiceInterface;
use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\Basket\Port\BasketRepositoryInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationEventRepositoryInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationChannelInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Notification\Domain\Port\NotificationEventInterface;
use MaarchCourrier\Notification\Domain\Port\NotificationRepositoryInterface;
use MaarchCourrier\Notification\Domain\Problem\NotificationIsDisabledProblem;
use MaarchCourrier\Notification\Domain\Problem\NotificationNotFoundProblem;
use Psr\Log\LoggerInterface;

class NotificationProcessor
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly BasketRepositoryInterface $basketRepository,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly BasketClauseServiceInterface $basketClauseService,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly NotificationEventRepositoryInterface $notificationEventRepository,
        private readonly NotificationRecipientFilter $notifRecipientFilter,
        private readonly NotificationChannelInterface $channel,      // Strategy for delivery (email, mobile, etc.)
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute the notification processing for a given notification string ID.
     *
     * @throws NotificationNotFoundProblem
     * @throws NotificationIsDisabledProblem
     */
    public function processNotification(string $notificationId): void
    {
        // Step 1: Load notification settings
        $notification = $this->notificationRepository->getByStringId($notificationId);
        if ($notification === null) {
            throw new NotificationNotFoundProblem($notificationId);
        }

        if (!$notification->isEnabled()) {
            throw new NotificationIsDisabledProblem($notification);
        }
        $this->logger->info("Loaded configuration for notification $notificationId");

        // Step 2: Scan baskets for new events
        /** @var BasketInterface[] $baskets */
        $baskets = $this->basketRepository->getNotifiableBasket();

        foreach ($baskets as $basket) {
            $this->logger->info("Processing basket {$basket->getId()}");
            // Get groups associated with this basket
            $groups = $this->groupRepository->getByBasketId($basket->getBasketId());

            foreach ($groups as $group) {
                // Get all users in this group
                $usersInGroup = $this->userRepository->getUsersByGroupId($group->getGroupId());
                // Filter users based on notification's diffusion settings
                $filteredUsers = $this->notifRecipientFilter->filterRecipients(
                    $notification,
                    $group,
                    $basket,
                    $usersInGroup
                );
                if (empty($filteredUsers)) {
                    continue;
                }
                foreach ($filteredUsers as $userToNotify) {
                    $realUser = null;
                    $user = $userToNotify;

                    // Prepare the basket clause for this user
                    $whereClause = $this->basketClauseService->prepare($basket, $userToNotify);
                    // Check basket redirection (if the user has delegated a basket)
                    $redirectedUser = $this->userRepository->getRedirectedUser($userToNotify, $basket, $group);
                    if ($redirectedUser !== null) {
                        $realUser = $userToNotify;
                        $user = $redirectedUser;
                    }
                    $this->logger->info("Processing user {$user->getLogin()} in basket {$basket->getId()}");
                    // Fetch resources that match the basket for this user
                    $isNotificationForDestUserOrCopyListAndPropertiesAreNotEmpty = in_array(
                        $notification->getDiffusionType(),
                        ['dest_user', 'copy_list']
                    ) && !empty($notification->getDiffusionProperties());
                    $resourcesWhere = $isNotificationForDestUserOrCopyListAndPropertiesAreNotEmpty ?
                        [$whereClause, 'status IN (?)'] : [$whereClause];
                    $resourcesData = $isNotificationForDestUserOrCopyListAndPropertiesAreNotEmpty ?
                        [$notification->getDiffusionProperties()] : [];

                    $resources = $this->mainResourceRepository->getOnViewByClause($resourcesWhere, $resourcesData);
                    if (empty($resources)) {
                        continue;
                    }
                    $logMsg = count($resources) . " document(s) to process for user";
                    $logMsg = "$logMsg {$userToNotify->getLogin()} in basket {$basket->getId()}";
                    $this->logger->info($logMsg);

                    // Construct event info text
                    $eventInfo = "Notification [{$basket->getBasketId()}] pour {$userToNotify->getLogin()}";
                    $notificationEvents = $this->notificationEventRepository->getRecordByInfoAndUser(
                        $eventInfo,
                        $userToNotify->getId(),
                        $realUser != null ? $user->getId() : null
                    );

                    $recordIds = array_flip(
                        array_map(
                            fn(NotificationEventInterface $event) => $event->getRecordId(),
                            $notificationEvents
                        )
                    );

                    // Create events for each new resource
                    $events = [];
                    foreach ($resources as $resource) {
                        if (!isset($recordIds[$resource->getResId()]) || empty($recordIds[$resource->getResId()])) {
                            $events[] = [
                                'res_letterbox',
                                $notification->getId(),
                                $resource->getResId(),
                                $user->getId(),
                                $eventInfo,
                                'CURRENT_TIMESTAMP'
                            ];
                        }
                    }
                    if (count($events) > 0) {
                        $this->logger->debug($eventInfo);
                        $this->notificationEventRepository->insertMultiple($events);
                    }
                } // end foreach user
            } // end foreach group
        } // end foreach basket

        // Step 3: Retrieve all pending events to process
        $this->logger->info("Scanning events for notification {$notification->getId()}");
        $notifEvents = $this->notificationEventRepository->getPendingEventsByNotification($notification);
        if (empty($notifEvents)) {
            $this->logger->info(
                "No event to process for notification {$notification->getId()} {$notification->getStringId()}"
            );
            return;
        }
        $this->logger->info(count($notifEvents) . " event(s) to process");

        // Group events by user and basket
        $groupedEvents = [];  // array of userId => array of basketId => [events]
        $usersInfo = array_combine(
            array_map(fn(NotificationEventInterface $event) => $event->getUser()->getId(), $notifEvents),
            array_map(fn(NotificationEventInterface $event) => $event->getUser(), $notifEvents)
        );

        foreach ($notifEvents as $event) {
            if (!in_array($event->getTableName(), ['res_letterbox', 'res_view_letterbox'])) {
                continue;
            }

            $userId = $event->getUser()->getId();
            preg_match_all('/\[([^]]+)]/', $event->getInfo(), $resultFromEventInfo);
            $basketId = $resultFromEventInfo[1][0];

            $groupedEvents[$userId][$basketId][] = $event;
        }

        // Step 4: Send notifications via the channel
        if ($this->channel->initialize($baskets)) {
            foreach ($groupedEvents as $userId => $basketsEvents) {
                /** @var UserInterface $user */
                $user = $usersInfo[$userId];
                $this->channel->sendNotification($notification, $user, $basketsEvents);
                // The channel implementation should handle marking events as sent or failed accordingly.
            }
        } else {
            $this->logger->info("Could not initialize sender for notification $notificationId");
        }

        $this->logger->info("End of process: notification $notificationId handled successfully");
    }
}
