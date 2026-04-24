<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Repository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Group\Infrastructure\Repository;

use Exception;
use Group\models\GroupModel;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupRepositoryInterface;
use MaarchCourrier\Group\Domain\Group;
use SrcCore\models\DatabaseModel;

class GroupRepository implements GroupRepositoryInterface
{
    /**
     * Create a ListInstance object from an array
     */
    private function createGroupFromData(array $data): GroupInterface
    {
        $externalId = !empty($data['external_id']) ? json_decode($data['external_id'], true) : null;

        return (new Group())
            ->setId($data['id'])
            ->setGroupId($data['group_id'])
            ->setLabel($data['group_desc'])
            ->setExternalId($externalId);
    }

    /**
     * @param int $id
     *
     * @return GroupInterface|null
     * @throws Exception
     */
    public function getById(int $id): GroupInterface|null
    {
        $group = GroupModel::get([
            'select' => ['*'],
            'where'  => ['id = ?'],
            'data'   => [$id]
        ]);

        return !empty($group) ? $this->createGroupFromData($group[0]) : null;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getGroupsWithoutLink(): array
    {
        $groupsDB = GroupModel::get([
            'select' => ['*'],
            'where'  => ["external_id->>'internalParapheur' IS NULL"]
        ]);

        return array_map(fn(array $item) => $this->createGroupFromData($item), $groupsDB);
    }

    /**
     * @param GroupInterface $group
     * @param array $values
     * @return void
     * @throws Exception
     */
    public function updateGroup(GroupInterface $group, array $values): void
    {
        GroupModel::update([
            'set'   => $values,
            'where' => ['group_id = ?'],
            'data'  => [$group->getGroupId()]
        ]);
    }

    /**
     * @param GroupInterface $group
     * @return void
     * @throws Exception
     */
    public function removeSignatureBookLink(GroupInterface $group): void
    {
        GroupModel::update([
            'postSet' => ['external_id' => "external_id - 'internalParapheur'"],
            'where'   => ['group_id = ?'],
            'data'    => [$group->getGroupId()]
        ]);
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function getByBasketId(string $basketId): array
    {
        $data = DatabaseModel::select([
            'select' => ['usergroups.*'],
            'table'  => ['usergroups, groupbasket'],
            'where'  => ['usergroups.group_id = groupbasket.group_id', 'groupbasket.basket_id = ?'],
            'data'   => [$basketId]
        ]);

        return array_map(fn(array $item) => $this->createGroupFromData($item), $data);
    }
}
