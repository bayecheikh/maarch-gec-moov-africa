<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Email Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Domain\Port;

use DateTimeImmutable;

interface NotificationEmailInterface
{
    public function getId(): int;
    public function setId(int $id): self;
    public function getReplyTo(): ?string;
    public function setReplyTo(?string $replyTo): self;
    public function getRecipient(): string;
    public function setRecipient(string $recipient): self;
    public function getCc(): ?string;
    public function setCc(?string $cc): self;
    public function getBcc(): ?string;
    public function setBcc(?string $bcc): self;
    public function getSubject(): ?string;
    public function setSubject(?string $subject): self;
    public function getBody(): ?string;
    public function setBody(?string $body): self;
    public function isBodyHtml(): bool;
    public function getAttachments(): ?array;
    public function setAttachments(?array $attachments): self;
    public function getExecutionDate(): ?DateTimeImmutable;
    public function setExecutionDate(?DateTimeImmutable $date): self;
    public function getExecutionStatus(): ?string;
    public function setExecutionStatus(?string $status): self;
}
