<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Event Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Domain\Port;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface NotificationEventInterface
{
    public function convertToDbItem(): array;

    public function getId(): int;

    public function setId(int $id): self;

    public function getNotification(): NotificationInterface;

    public function setNotification(NotificationInterface $notification): self;

    public function getTableName(): string;

    public function setTableName(string $tableName): self;

    public function getRecordId(): string;

    public function setRecordId(string $recordId): self;

    public function getUser(): UserInterface;

    public function setUser(UserInterface $user): self;

    public function getInfo(): string;

    public function setInfo(string $eventInfo): self;

    public function getDate(): DateTimeImmutable;

    public function setDate(DateTimeImmutable $dateTime): self;

    public function getExecDate(): ?DateTimeImmutable;

    public function setExecDate(?DateTimeImmutable $dateTime): self;

    public function getResult(): ?string;

    public function setResult(?string $result): self;
}
