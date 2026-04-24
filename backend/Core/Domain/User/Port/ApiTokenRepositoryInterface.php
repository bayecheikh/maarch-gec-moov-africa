<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Api Token Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User\Port;

use DateTimeImmutable;
use MaarchCourrier\User\Domain\Port\ApiTokenInterface;

interface ApiTokenRepositoryInterface
{
    public function getByUser(UserInterface $user): ?ApiTokenInterface;

    public function getAllTokens(): array;

    public function save(ApiTokenInterface $token): void;

    public function delete(ApiTokenInterface $token): void;

    public function updateLastUsedDate(ApiTokenInterface $token): void;

    public function generateToken(
        int $userId,
        DateTimeImmutable $creationDate,
        DateTimeImmutable $expirationDate
    ): string;
}
