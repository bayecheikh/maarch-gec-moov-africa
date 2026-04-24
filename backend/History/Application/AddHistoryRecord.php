<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Add History Record
 * @author dev@maarch.org
 */

namespace MaarchCourrier\History\Application;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationsEventsServiceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\History\Domain\HistoryRecord;
use MaarchCourrier\History\Domain\Port\HistoryRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class AddHistoryRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HistoryRepositoryInterface $historyRepository,
        private readonly NotificationsEventsServiceInterface $notificationsEventsService
    ) {
    }

    public function add(
        string $tableName,
        string $recordId,
        string $eventId,
        string $eventType,
        string $info,
        string|DateTimeImmutable $eventDate = null,
        ?UserInterface $user = null,
        ?string $moduleId = null,
        ?string $logLevel = null
    ): void {
        $record = (new HistoryRecord())
            ->setId(-1)
            ->setTableName($tableName)
            ->setRecordId($recordId)
            ->setEventId($eventId)
            ->setEventType($eventType)
            ->setModuleId($moduleId)
            ->setInfo($info)
            ->setUser($user);

        $isRecordEventDateSet = false;
        if ($eventDate !== null) {
            if (!empty($eventDate) && is_string($eventDate)) {
                $eventDate = new DateTimeImmutable($eventDate);
            }
            $record->setEventDate($eventDate);
            $isRecordEventDateSet = true;
        }

        if ($logLevel === null) {
            $logLevel = LogLevel::DEBUG;
        } else {
            $logLevel = match (strtolower($logLevel)) {
                LogLevel::EMERGENCY => LogLevel::EMERGENCY,
                LogLevel::ALERT => LogLevel::ALERT,
                LogLevel::CRITICAL => LogLevel::CRITICAL,
                LogLevel::ERROR => LogLevel::ERROR,
                LogLevel::WARNING => LogLevel::WARNING,
                LogLevel::NOTICE => LogLevel::NOTICE,
                LogLevel::INFO => LogLevel::INFO,
                default => LogLevel::DEBUG
            };
        }

        $this->logger->$logLevel($info, $record->jsonSerialize());

        $this->historyRepository->save($record, !$isRecordEventDateSet);
        $this->notificationsEventsService->fillEventStack($record->jsonSerialize());
    }
}
