<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Shipping
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Entity\Port\EntityInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

class Shipping
{
    private ?int $id = null;
    private UserInterface $user;
    private MainResourceInterface|AttachmentInterface $resource;
    private array $options = [];
    private float $fee;
    private EntityInterface $recipientEntity;
    private array $recipients = [];
    private string $accountId;
    private DateTimeImmutable $creationDate;
    private array $history = [];
    private ?string $sendingId = null;
    private array $attachments = [];
    private int $actionId;
    private ?MailevaTemplate $mailevaTemplate = null;

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getResource(): MainResourceInterface|AttachmentInterface
    {
        return $this->resource;
    }

    public function getResourceType(): string
    {
        return $this->resource instanceof MainResourceInterface ? 'resource' : 'attachment';
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getFee(): float
    {
        return $this->fee;
    }

    public function getRecipientEntity(): EntityInterface
    {
        return $this->recipientEntity;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getCreationDate(): DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function getHistory(): array
    {
        return $this->history;
    }

    public function getSendingId(): ?string
    {
        return $this->sendingId;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getActionId(): int
    {
        return $this->actionId;
    }

    public function getMailevaTemplate(): ?MailevaTemplate
    {
        return $this->mailevaTemplate;
    }

    // Setters
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setUser(UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function setResource(MainResourceInterface|AttachmentInterface $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function setFee(float $fee): self
    {
        $this->fee = $fee;
        return $this;
    }

    public function setRecipientEntity(EntityInterface $recipientEntity): self
    {
        $this->recipientEntity = $recipientEntity;
        return $this;
    }

    public function setRecipients(array $recipients): self
    {
        $this->recipients = $recipients;
        return $this;
    }

    public function setAccountId(string $accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }

    public function setCreationDate(DateTimeImmutable $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function setHistory(array $history): self
    {
        $this->history = $history;
        return $this;
    }

    public function setSendingId(?string $sendingId): self
    {
        $this->sendingId = $sendingId;
        return $this;
    }

    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function setActionId(int $actionId): self
    {
        $this->actionId = $actionId;
        return $this;
    }

    public function setMailevaTemplate(?MailevaTemplate $mailevaTemplate): self
    {
        $this->mailevaTemplate = $mailevaTemplate;
        return $this;
    }
}
