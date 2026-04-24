<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email Notification Sender
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Infrastructure;

use ContentManagement\controllers\MergeController;
use Docserver\models\DocserverModel;
use Exception;
use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationEventRepositoryInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationChannelInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Notification\Domain\NotificationEmail;
use MaarchCourrier\Notification\Domain\Port\NotificationEmailRepositoryInterface;
use MaarchCourrier\Notification\Domain\Port\NotificationEventInterface;
use Psr\Log\LoggerInterface;
use Resource\models\ResModel;
use SrcCore\models\CoreConfigModel;

class BasketEmailNotificationChannel implements NotificationChannelInterface
{
    /**
     * @var BasketInterface[] $baskets
     */
    private array $baskets;
    private string $maarchUrl;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly NotificationEmailRepositoryInterface $notificationEmailRepository,
        private readonly NotificationEventRepositoryInterface $notificationEventRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getEventInfoPrefix(): string
    {
        return "Notification";
    }

    /**
     * @inheritDoc
     */
    public function initialize($baskets): bool
    {
        $this->baskets = $baskets;
        try {
            $config = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
            $this->maarchUrl = $config['config']['maarchUrl'] ?? '';
            return true;
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
     * @throws Exception
     */
    public function sendNotification(
        NotificationInterface $notification,
        UserInterface $recipient,
        array $basketsEvents
    ): void {
        $recipientEmail = $recipient->getMail();
        if (empty($recipientEmail)) {
            $this->logger->error("{$recipient->getFullName()} has no email to send to!");
            return;
        }

        $attachMode = $notification->getAttachForType(); // Only have 'main_document'
        $eventsToMarkSuccess = [];  // will collect event IDs sent successfully
        $emailCount = 0;

        // If recap, combine all events into one email
        if ($notification->isSendAsRecap()) {
            $combinedEvents = [];
            $combinedAttachments = [];
            foreach ($basketsEvents as $basketId => $events) {
                // Attach basket name for template usage
                $basketName = array_filter($this->baskets, fn($basket) => $basket->getBasketId() === $basketId);
                $basketName = reset($basketName);
                $basketName = $basketName ? $basketName->getName() : $basketId;

                /**
                 * @var NotificationEventInterface[] $events
                 */
                foreach ($events as $event) {
                    $eventItem = $event->convertToDbItem();
                    $eventItem['basketName'] = $basketName;
                    $combinedEvents[] = $eventItem;
                }

                // Attach documents if needed
                if (!empty($attachMode)) {
                    $combinedAttachments = array_merge(
                        $combinedAttachments,
                        $this->collectAttachments($attachMode, $events)
                    );
                }
            }
            $combinedAttachments = array_unique($combinedAttachments);
            $subject = $notification->getDescription();
            // Merge template at once with all events
            $htmlBody = MergeController::mergeNotification([
                'templateId' => $notification->getTemplateId(),
                'params'     => [
                    'recipient'    => $recipient->jsonSerialize(),
                    'events'       => $combinedEvents,
                    'notification' => $notification->convertToDbItem(),
                    'maarchUrl'    => $this->maarchUrl,
                    'coll_id'      => 'letterbox_coll',
                    'res_table'    => 'res_letterbox',
                    'res_view'     => 'res_view_letterbox'
                ]
            ]);
            if (!$htmlBody) {
                // Mark all events failed
                $eventsToMarkError = array_unique(array_map(fn($e) => $e, $combinedEvents), SORT_REGULAR);
                $this->markEventsFailed($eventsToMarkError, "Error merging template");
                return;
            }
            // Queue email
            $notifEmail = (new NotificationEmail())
                ->setRecipient($recipientEmail)
                ->setSubject($subject)
                ->setBody($this->sanitizeHtml($htmlBody))
                ->setAttachments($combinedAttachments);
            $this->notificationEmailRepository->insert($notifEmail);
            // Mark all events as success
            $eventsToMarkSuccess = array_map(fn($e) => $e['event_stack_sid'], $combinedEvents);
            $emailCount++;
        } else {
            // Non-recap: send one email per basket
            foreach ($basketsEvents as $basketId => $events) {
                $basketName = array_filter($this->baskets, fn($basket) => $basket->getBasketId() === $basketId);
                $basketName = reset($basketName);
                $basketName = $basketName ? $basketName->getName() : $basketId;

                // Convert an event to an array and tag basketName in each event for a template
                $convertedEventsOfBasket = [];
                /**
                 * @var NotificationEventInterface[] $events
                 */
                foreach ($events as $event) {
                    $eventItem = $event->convertToDbItem();
                    $eventItem['basketName'] = $basketName;
                    $convertedEventsOfBasket[] = $eventItem;
                }

                $subject = $notification->getDescription();
                $htmlBody = MergeController::mergeNotification([
                    'templateId' => $notification->getTemplateId(),
                    'params'     => [
                        'recipient'    => $recipient->jsonSerialize(),
                        'events'       => $convertedEventsOfBasket,
                        'notification' => $notification->convertToDbItem(),
                        'maarchUrl'    => $this->maarchUrl,
                        'coll_id'      => 'letterbox_coll',
                        'res_table'    => 'res_letterbox',
                        'res_view'     => 'res_view_letterbox'
                    ]
                ]);

                if (!$htmlBody) {
                    $this->markEventsFailed($events, "Error merging template");
                    continue; // skip to the next basket
                }
                // Attach documents if needed
                $attachments = !empty($attachMode) ? $this->collectAttachments($attachMode, $events) : [];
                $notifEmail = (new NotificationEmail())
                    ->setRecipient($recipientEmail)
                    ->setSubject($subject)
                    ->setBody($this->sanitizeHtml($htmlBody))
                    ->setAttachments($attachments);
                $this->notificationEmailRepository->insert($notifEmail);
                // accumulate events sent
                $eventsToMarkSuccess = array_merge($eventsToMarkSuccess, array_map(fn($e) => $e->getId(), $events));
                $emailCount++;
            }
        }

        // Mark events as sent successfully in DB
        if (!empty($eventsToMarkSuccess)) {
            $this->notificationEventRepository->setExecResultForIds("SUCCESS", $eventsToMarkSuccess);
        }

        $this->logger->info(
            get_class($this) . " end of process: Notification email(s) created $emailCount and " .
            count($eventsToMarkSuccess) . " events processed."
        );
    }

    /**
     * @param string $type
     * @param NotificationEventInterface[] $events
     * @return array
     * @throws Exception
     */
    private function collectAttachments(string $type, array $events): array
    {
        $attachments = [];
        foreach ($events as $event) {
            if (empty($event->getRecordId())) {
                continue;
            }
            $res = ResModel::getById([
                'resId'  => $event->getRecordId(),
                'select' => ['path', 'filename', 'docserver_id']
            ]);
            if (empty($res)) {
                continue;
            }
            if ($type == 'main_document' && !empty($res['docserver_id'])) {
                $doc = DocserverModel::getByDocserverId([
                    'docserverId' => $res['docserver_id'],
                    'select'      => ['path_template']
                ]);
                $base = $doc['path_template'] ?? '';
                $filePath = $base . str_replace('#', DIRECTORY_SEPARATOR, $res['path']) . $res['filename'];
                $filePath = str_replace(['//', '\\'], ['/', '/'], $filePath);
                if (is_file($filePath)) {
                    $attachments[] = $filePath;
                }
            }
        }
        return $attachments;
    }

    private function sanitizeHtml(string $html): string
    {
        // Reverse some encodings that shouldn't be double-encoded in the email body
        $html = str_replace("&#039;", "'", $html);
        $html = str_replace('&amp;', '&', $html);
        // Replace lone '&' with something safe if needed (the original code replaced '&' with '#and#')
        return str_replace('&', '#and#', $html);
    }

    /**
     * @param NotificationEventInterface[] $events
     * @param string $reason
     * @return void
     */
    private function markEventsFailed(array $events, string $reason): void
    {
        $ids = array_map(fn($e) => $e->getId(), $events);
        if (!empty($ids)) {
            $this->notificationEventRepository->setExecResultForIds("FAILED: $reason", $ids);
        }
    }
}
