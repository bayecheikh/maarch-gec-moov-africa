<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Api Token class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\User\Domain;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\User\Domain\Port\ApiTokenInterface;

class ApiToken implements ApiTokenInterface
{
    private UserInterface $user;
    private string $token;
    private DateTimeImmutable $creationDate;
    private DateTimeImmutable $expirationDate;
    private ?DateTimeImmutable $lastUsedDate = null;

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): ApiTokenInterface
    {
        $this->user = $user;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): ApiTokenInterface
    {
        $this->token = $token;
        return $this;
    }

    public function getCreationDate(): DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTimeImmutable $creationDate): ApiTokenInterface
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getExpirationDate(): DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(DateTimeImmutable $expirationDate): ApiTokenInterface
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function getLastUsedDate(): ?DateTimeImmutable
    {
        return $this->lastUsedDate;
    }

    public function setLastUsedDate(?DateTimeImmutable $lastUsedDate): ApiTokenInterface
    {
        $this->lastUsedDate = $lastUsedDate;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expirationDate < new DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'creation_date'   => $this->creationDate->format('Y-m-d H:i:s'),
            'token'           => $this->token,
            'expiration_date' => $this->expirationDate->format('Y-m-d H:i:s'),
            'last_used_date'  => $this->lastUsedDate?->format('Y-m-d H:i:s')
        ];
    }
}
