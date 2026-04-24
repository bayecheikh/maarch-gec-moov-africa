<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Entity Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Entity\Port;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface EntityRepositoryInterface
{
    public function getEntityById(int $id): ?EntityInterface;

    public function getEntityByEntityId(string $entityId): ?EntityInterface;

    public function getEntityByEntityIds(array $entityIds): array;

    /**
     * @param UserInterface $user
     * @return EntityInterface[]
     */
    public function getEntitiesForUser(UserInterface $user): array;
}
