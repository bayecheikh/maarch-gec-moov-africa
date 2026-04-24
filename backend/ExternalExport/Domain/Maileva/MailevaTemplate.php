<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Template
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva;

use DateTimeImmutable;
use JsonSerializable;

class MailevaTemplate implements JsonSerializable
{
    private int $id;
    private string $label;
    private string $description;
    private array $options;
    private array $fee;
    private ?array $entities = null;
    private array $account;
    private array $subscriptions;
    private DateTimeImmutable $tokenMinLat;

    // Getter and Setter for id
    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    // Getter and Setter for label
    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    // Getter and Setter for description
    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    // Getter and Setter for options
    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    // Getter and Setter for fee
    public function getFee(): array
    {
        return $this->fee;
    }

    public function setFee(array $fee): self
    {
        $this->fee = $fee;
        return $this;
    }

    // Getter and Setter for entities
    public function getEntities(): ?array
    {
        return $this->entities;
    }

    public function setEntities(?array $entities): self
    {
        $this->entities = $entities;
        return $this;
    }

    // Getter and Setter for account
    public function getAccount(): array
    {
        return $this->account;
    }

    public function setAccount(array $account): self
    {
        $this->account = $account;
        return $this;
    }

    // Getter and Setter for subscriptions
    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    public function setSubscriptions(array $subscriptions): self
    {
        $this->subscriptions = $subscriptions;
        return $this;
    }

    // Getter and Setter for tokenMinLat
    public function getTokenMinLat(): DateTimeImmutable
    {
        return $this->tokenMinLat;
    }

    public function setTokenMinLat(DateTimeImmutable $tokenMinLat): self
    {
        $this->tokenMinLat = $tokenMinLat;
        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id'          => $this->id,
            'label'       => $this->label,
            'description' => $this->description,
            'options'     => $this->options,
            'fee'         => $this->fee
        ];
    }
}
