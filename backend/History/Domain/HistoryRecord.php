<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief History Record
 * @author dev@maarch.org
 */

namespace MaarchCourrier\History\Domain;

use DateTimeImmutable;
use JsonSerializable;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

class HistoryRecord implements JsonSerializable
{
    private int $id;
    private ?string $tableName = null;
    private ?string $eventId = null;
    private string $eventType;
    private string $moduleId = 'admin';
    private ?string $recordId = null;
    private ?UserInterface $user = null;
    private DateTimeImmutable $eventDate;
    private ?string $info = null;
    private ?string $remoteIp = null;

    public function __construct()
    {
        $this->eventDate = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function setTableName(?string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function getRecordId(): ?string
    {
        return $this->recordId;
    }

    public function setRecordId(?string $recordId): self
    {
        $this->recordId = $recordId;
        return $this;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getEventDate(): DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function setEventDate(DateTimeImmutable $eventDate): self
    {
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(?string $info): self
    {
        $this->info = $info;
        return $this;
    }

    public function getModuleId(): string
    {
        return $this->moduleId;
    }

    public function setModuleId(?string $moduleId): self
    {
        $this->moduleId = ($moduleId === null ? 'admin' : $moduleId);
        return $this;
    }

    public function getRemoteIp(): ?string
    {
        return $this->remoteIp;
    }

    public function setRemoteIp(?string $remoteIp): self
    {
        $this->remoteIp = $remoteIp;
        return $this;
    }

    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    public function setEventId(?string $eventId): self
    {
        $this->eventId = $eventId;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->inArray(true);
    }

    public function inArray(bool $isConfidential = false): array
    {
        $return = [
            'id'        => $this->getId(),
            'tableName' => $this->getTableName(),
            'recordId'  => $this->getRecordId(),
            'eventId'   => $this->getEventId(),
            'eventType' => $this->getEventType(),
            'moduleId'  => $this->getModuleId(),
            'userId'    => $this->getUser()?->getId(),
            'eventDate' => $this->getEventDate(),
            'info'      => $this->getInfo()
        ];

        if (!$isConfidential) {
            $return['remoteIp'] = $this->getRemoteIp();
        }

        return $return;
    }
}
