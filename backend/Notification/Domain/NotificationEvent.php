<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Event
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Domain;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Notification\Domain\Port\NotificationEventInterface;

class NotificationEvent implements NotificationEventInterface
{
    private int $id;
    private NotificationInterface $notification;
    private string $tableName;
    private string $recordId;
    private UserInterface $user;
    private string $info;
    private DateTimeImmutable $date;
    private ?DateTimeImmutable $execDate = null;
    private ?string $result = null;

    public function convertToDbItem(): array
    {
        return [
            'event_stack_sid'  => $this->getId(),
            'notification_sid' => $this->getNotification()->getId(),
            'table_name'       => $this->getTableName(),
            'record_id'        => $this->getRecordId(),
            'user_id'          => $this->getUser()->getId(),
            'event_info'       => $this->getInfo(),
            'event_date'       => $this->getDate()->format('Y-m-d H:i:s.u'),
            'exec_date'        => $this->getExecDate()?->format('Y-m-d H:i:s.u'),
            'result'           => $this->getResult()
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): NotificationEventInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getNotification(): NotificationInterface
    {
        return $this->notification;
    }

    public function setNotification(NotificationInterface $notification): NotificationEventInterface
    {
        $this->notification = $notification;
        return $this;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): NotificationEventInterface
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function getRecordId(): string
    {
        return $this->recordId;
    }

    public function setRecordId(string $recordId): NotificationEventInterface
    {
        $this->recordId = $recordId;
        return $this;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): NotificationEventInterface
    {
        $this->user = $user;
        return $this;
    }

    public function getInfo(): string
    {
        return $this->info;
    }

    public function setInfo(string $eventInfo): NotificationEventInterface
    {
        $this->info = $eventInfo;
        return $this;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $dateTime): NotificationEventInterface
    {
        $this->date = $dateTime;
        return $this;
    }

    public function getExecDate(): ?DateTimeImmutable
    {
        return $this->execDate;
    }

    public function setExecDate(?DateTimeImmutable $dateTime): NotificationEventInterface
    {
        $this->execDate = $dateTime;
        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): NotificationEventInterface
    {
        $this->result = $result;
        return $this;
    }
}
