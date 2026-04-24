<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Email Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Domain;

use DateTimeImmutable;
use MaarchCourrier\Notification\Domain\Port\NotificationEmailInterface;

class NotificationEmail implements NotificationEmailInterface
{
    private int $id;
    private ?string $replyTo = null;
    private string $recipient;
    private ?string $cc = null;
    private ?string $bcc = null;
    private ?string $subject = null;
    private ?string $body = null;
    private bool $bodyHtml = true;
    private ?array $attachments = null;
    private ?DateTimeImmutable $executionDate = null;
    private ?string $executionStatus = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): NotificationEmailInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }

    public function setReplyTo(?string $replyTo): NotificationEmailInterface
    {
        $this->replyTo = $replyTo;
        return $this;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): NotificationEmailInterface
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getCc(): ?string
    {
        return $this->cc;
    }

    public function setCc(?string $cc): NotificationEmailInterface
    {
        $this->cc = $cc;
        return $this;
    }

    public function getBcc(): ?string
    {
        return $this->bcc;
    }

    public function setBcc(?string $bcc): NotificationEmailInterface
    {
        $this->bcc = $bcc;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): NotificationEmailInterface
    {
        $this->subject = $subject;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): NotificationEmailInterface
    {
        $this->body = $body;
        return $this;
    }

    public function isBodyHtml(): bool
    {
        return $this->bodyHtml;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): NotificationEmailInterface
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function getExecutionDate(): ?DateTimeImmutable
    {
        return $this->executionDate;
    }

    public function setExecutionDate(?DateTimeImmutable $date): NotificationEmailInterface
    {
        $this->executionDate = $date;
        return $this;
    }

    public function getExecutionStatus(): ?string
    {
        return $this->executionStatus;
    }

    public function setExecutionStatus(?string $status): NotificationEmailInterface
    {
        $this->executionStatus = $status;
        return $this;
    }
}
