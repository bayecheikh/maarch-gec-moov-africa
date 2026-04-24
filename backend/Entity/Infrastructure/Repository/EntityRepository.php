<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Entity Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Entity\Infrastructure\Repository;

use Entity\models\EntityModel;
use Exception;
use MaarchCourrier\Core\Domain\Entity\Port\EntityInterface;
use MaarchCourrier\Core\Domain\Entity\Port\EntityRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Entity\Domain\Entity;
use SrcCore\models\DatabaseModel;

class EntityRepository implements EntityRepositoryInterface
{
    /**
     * @param int $id
     * @return EntityInterface|null
     * @throws Exception
     */
    public function getEntityById(int $id): ?EntityInterface
    {
        if ($id <= 0) {
            return null;
        }

        $entity = EntityModel::getById([
            'id'     => $id,
            'select' => ['*']
        ]);

        if (empty($entity)) {
            return null;
        }

        return Entity::createFromArray($entity);
    }

    /**
     * @param string $entityId
     * @return EntityInterface|null
     * @throws Exception
     */
    public function getEntityByEntityId(string $entityId): ?EntityInterface
    {
        $entity = EntityModel::getByEntityId([
            'entityId' => $entityId
        ]);

        if (empty($entity)) {
            return null;
        }

        return Entity::createFromArray($entity);
    }

    /**
     * @param string[] $entityIds
     * @return EntityInterface[]
     * @throws Exception
     */
    public function getEntityByEntityIds(array $entityIds): array
    {
        if (empty($entityIds)) {
            return [];
        }

        $dbEntities = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['entities'],
            'where'  => ['entity_id in (?)'],
            'data'   => [$entityIds]
        ]);

        $entities = [];

        foreach ($dbEntities as $entity) {
            $entities[$entity['entity_id']] = Entity::createFromArray($entity);
        }

        return $entities;
    }

    /**
     * @param UserInterface $user
     * @return EntityInterface[]
     * @throws Exception
     */
    public function getEntitiesForUser(UserInterface $user): array
    {
        $dbEntities = DatabaseModel::select([
            'select' => ['entities.*'],
            'table'  => ['users_entities', 'entities'],
            'where'  => ['users_entities.user_id = ?', 'users_entities.entity_id = entities.entity_id'],
            'data'   => [$user->getId()]
        ]);

        $entities = [];

        foreach ($dbEntities as $entity) {
            $entities[] = Entity::createFromArray($entity);
        }

        return $entities;
    }
}
