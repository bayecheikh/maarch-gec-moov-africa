<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Group\Port;

interface GroupRepositoryInterface
{
    public function getById(int $id): GroupInterface|null;

    public function getGroupsWithoutLink(): array;

    public function updateGroup(GroupInterface $group, array $values): void;

    public function removeSignatureBookLink(GroupInterface $group): void;

    /**
     * @param string $basketId
     * @return GroupInterface[]
     */
    public function getByBasketId(string $basketId): array;
}
