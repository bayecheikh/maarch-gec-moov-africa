<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Domain\Port;

interface EmailRepositoryInterface
{
    public function getById(int $id): ?EmailInterface;

    public function updateEmail(EmailInterface $email, array $set): void;

    public function doesEmailExist(EmailInterface $email): bool;
}
