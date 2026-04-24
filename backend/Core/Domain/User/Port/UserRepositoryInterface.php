<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User\Port;

use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;

interface UserRepositoryInterface
{
    public function getByLogin(string $login): ?UserInterface;

    public function getUserById(int $userId): ?UserInterface;

    public function getUsersByIds(array $userIds): array;

    public function getUsersWithoutLink(): array;

    public function updateUser(UserInterface $user, array $values): void;

    public function getGroupsById(UserInterface $user): array;

    public function addSignatorySubstitute(UserInterface $ownerSignatory, UserInterface $signatorySubstitutes): void;

    public function removeSignatorySubstitute(UserInterface $ownerSignatory, UserInterface $signatorySubstitute): void;

    public function getOwnerIdsBySignatorySubstituteId(int $substituteId): array;

    public function getSuperAdmin(): UserInterface;

    /**
     * @return UserInterface[]
     */
    public function getRootUsers(): array;

    /**
     * @return UserInterface[]
     */
    public function getAllWebServiceUsers(): array;

    public function getUserLinkedWithProConnect(array $proConnect): UserInterface|array;

    public function linkUserWithProConnect(UserInterface $user, array $userInfos): void;

    public function getUsersFromEntityStringIds(array $entityIds): array;

    public function getUsersByGroupId(string $groupId): array;

    /**
     * @param string[] $entityStringIds
     * @return array
     */
    public function getUsersByEntityStringIds(array $entityStringIds): array;

    public function getRedirectedUser(
        UserInterface $owner,
        BasketInterface $basket,
        GroupInterface $group
    ): UserInterface|null;
}
