<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Api Token Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Domain\Port;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface ApiTokenInterface
{
    public function getUser(): UserInterface;
    public function setUser(UserInterface $user): ApiTokenInterface;
    public function getToken(): string;
    public function setToken(string $token): ApiTokenInterface;
    public function getCreationDate(): DateTimeImmutable;
    public function setCreationDate(DateTimeImmutable $creationDate): ApiTokenInterface;
    public function getExpirationDate(): DateTimeImmutable;
    public function setExpirationDate(DateTimeImmutable $expirationDate): ApiTokenInterface;
    public function getLastUsedDate(): ?DateTimeImmutable;
    public function setLastUsedDate(?DateTimeImmutable $lastUsedDate): ApiTokenInterface;
    public function isExpired(): bool;
    public function toArray(): array;
}
