<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Workflow Item class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain;

use DateTimeImmutable;
use JsonSerializable;

class WorkflowItem implements JsonSerializable
{
    private ?int $userId = null;
    private string $userDisplay;
    private ?string $mode = null;
    private string $status;
    private int $order;
    private ?DateTimeImmutable $processDate = null;
    private bool $isSystem = false;

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserDisplay(): string
    {
        return $this->userDisplay;
    }

    public function setUserDisplay(string $userDisplay): self
    {
        $this->userDisplay = $userDisplay;
        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(?string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getProcessDate(): ?DateTimeImmutable
    {
        return $this->processDate;
    }

    public function setProcessDate(?DateTimeImmutable $processDate): self
    {
        $this->processDate = $processDate;
        return $this;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): self
    {
        $this->isSystem = $isSystem;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'userId'      => $this->getUserId(),
            'userDisplay' => $this->getUserDisplay(),
            'mode'        => $this->getMode(),
            'status'      => $this->getStatus(),
            'order'       => $this->getOrder(),
            'processDate' => $this->getProcessDate()?->format('Y-m-d H:i:s'),
            'isSystem'    => $this->isSystem()
        ];
    }
}
