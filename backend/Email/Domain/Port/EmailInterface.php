<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Domain\Port;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Email\Domain\EmailStatus;

interface EmailInterface
{
    public static function createFromArray(array $data): self;

    public function getId(): int;

    public function setId(int $id): self;

    public function getUser(): UserInterface;

    public function setUser(UserInterface $user): self;

    public function getSender(): array;

    public function setSender(array $sender): self;

    public function getRecipients(): array;

    public function setRecipients(array $recipients): self;

    public function getCc(): array;

    public function setCc(array $cc): self;

    public function getCci(): array;

    public function setCci(array $cci): self;

    public function getObject(): string;

    public function setObject(string $object): self;

    public function getBody(): string;

    public function setBody(string $body): self;

    public function getDocuments(): array;

    public function setDocuments(array $documents): self;

    public function isHtml(): bool;

    public function setIsHtml(bool $isHtml): self;

    public function getStatus(): EmailStatus;

    public function setStatus(EmailStatus $status): self;

    public function getMessageExchangeId(): ?int;

    public function setMessageExchangeId(?int $messageExchangeId): self;

    public function getCreationDate(): DateTimeImmutable;

    public function setCreationDate(DateTimeImmutable $creationDate): self;

    public function getSendDate(): DateTimeImmutable;

    public function setSendDate(DateTimeImmutable $sendDate): self;
}
