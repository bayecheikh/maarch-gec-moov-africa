<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Mobile Notification Sender
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Infrastructure\MaarchMobile;

use Exception;
use Google\Client;
use Google\Service\FirebaseCloudMessaging;
use Google\Service\FirebaseCloudMessaging\Message;
use Google\Service\FirebaseCloudMessaging\Notification as FCMNotification;
use Google\Service\FirebaseCloudMessaging\SendMessageRequest;
use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationChannelInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationEventRepositoryInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Notification\Domain\Port\NotificationEventInterface;
use Psr\Log\LoggerInterface;
use SrcCore\models\CoreConfigModel;

class FirebaseCloudMessagingNotificationChannel implements NotificationChannelInterface
{
    /**
     * @var BasketInterface[] $baskets
     */
    private array $baskets;
    private string $projectId;
    private FirebaseCloudMessaging $fcmService;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly NotificationEventRepositoryInterface $notificationEventRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getEventInfoPrefix(): string
    {
        return 'Notification MCM';
    }

    /**
     * @inheritDoc
     */
    public function initialize(array $baskets): bool
    {
        $this->baskets = $baskets;
        try {
            $config = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
            if (!empty($config['config']['firebaseSdkServiceAccountJsonFile'] ?? null)) {
                $sdkConfig = CoreConfigModel::getJsonLoaded([
                    'path' => trim($config['config']['firebaseSdkServiceAccountJsonFile'])
                ]);
                $this->projectId = $sdkConfig['project_id'];
                $client = new Client();
                $client->setAuthConfig(trim($config['config']['firebaseSdkServiceAccountJsonFile']));
                $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
                $this->fcmService = new FirebaseCloudMessaging($client);
                return true;
            }
        } catch (Exception $e) {
            $this->logger->error(
                "Could not initialized " . get_class($this) . ": " . $e->getMessage(),
                $e->getTrace()
            );
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function sendNotification(
        NotificationInterface $notification,
        UserInterface $recipient,
        array $basketsEvents
    ): void {
        $tokens = $recipient->getExternalId()['tokenMCM'] ?? null;
        if (empty($tokens)) {
            $this->logger->info("No mobile token for user {$recipient->getId()} '{$recipient->getLogin()}'");
            return;
        }

        // Determine which basket events the user wants, based on preferences
        $preferences = $recipient->getExternalId()['preferenceNotifMCM'] ?? null;
        $messageLines = [];
        $eventIdsToMark = [];

        foreach ($basketsEvents as $basketId => $events) {
            // If a user has preferences and this basket is not preferred, skip it
            if (!empty($preferences) && !in_array($basketId, $preferences, true)) {
                $this->logger->debug("Basket '$basketId' not in user preferences, skipping...");
                continue;
            }
            // Count events for this basket and append a line to the push message
            $basketName = array_filter($this->baskets, fn($basket) => $basket->getBasketId() === $basketId);
            $basketName = reset($basketName);
            $basketName = $basketName ? $basketName->getName() : $basketId;
            $count = count($events);
            $messageLines[] = "Vous avez $count nouveau(x) courrier(s) dans la bannette $basketName.";

            // Mark these events to update as sent
            $eventIdsToMark = array_merge(
                $eventIdsToMark,
                array_map(fn(NotificationEventInterface $e) => $e->getId(), $events)
            );
        }

        if (empty($messageLines)) {
            $this->logger->info("No relevant events for user preferences...");
            return;
        }

        $fullMessage = implode("\n", $messageLines);

        // Send push notification via FCM
        foreach ($tokens as $token) {
            $this->sendViaFCM($token, $fullMessage);
        }

        // Update events as successfully sent
        if (!empty($eventIdsToMark)) {
            $this->notificationEventRepository->setExecResultForIds('SUCCESS', $eventIdsToMark);
        }
    }

    private function sendViaFCM(string $token, string $message): void
    {
        try {
            $fcmMessage = new Message();
            $fcmMessage->setToken($token);
            $fcmMessage->setNotification(new FCMNotification([
                'title' => 'Maarch Courrier Mobile',
                'body'  => $message,
            ]));
            $request = new SendMessageRequest();
            $request->setMessage($fcmMessage);
            $this->fcmService->projects_messages->send('projects/' . $this->projectId, $request);
            $this->logger->info("Sent mobile notification to token $token");
        } catch (Exception $e) {
            $this->logger->error("Error sending FCM notification to $token: " . $e->getMessage(), $e->getTrace());
        }
    }
}
