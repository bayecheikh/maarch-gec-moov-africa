<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Repository Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Infrastructure\Repository;

use Exception;
use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\UserMode;
use MaarchCourrier\Group\Domain\Group;
use MaarchCourrier\User\Domain\User;
use SrcCore\models\DatabaseModel;
use User\models\UserModel;

class UserRepository implements UserRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function getByLogin(string $login): ?UserInterface
    {
        if (empty($login)) {
            return null;
        }

        $user = UserModel::getByLogin(['select' => ['*'], 'login' => $login]);

        if (empty($user)) {
            return null;
        }

        return User::createFromArray($user);
    }

    /**
     * @param int $userId
     * @return ?UserInterface
     * @throws Exception
     */
    public function getUserById(int $userId): ?UserInterface
    {
        if ($userId <= 0) {
            return null;
        }

        $user = UserModel::getById([
            'id'     => $userId,
            'select' => ['*']
        ]);

        if (empty($user)) {
            return null;
        }

        return User::createFromArray($user);
    }

    /**
     * @param int[] $userIds
     *
     * @return UserInterface[]
     * @throws Exception
     */
    public function getUsersByIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $usersDB = UserModel::get([
            'select' => ['*'],
            'where'  => ["id IN (?)"],
            'data'   => [$userIds]
        ]);

        $users = [];

        foreach ($usersDB as $user) {
            $users[$user['id']] = User::createFromArray($user);
        }

        return $users;
    }

    /**
     * @return UserInterface[]
     * @throws Exception
     */
    public function getUsersWithoutLink(): array
    {
        $users = [];

        $usersDB = UserModel::get([
            'select' => ['*'],
            'where'  => ["external_id->>'internalParapheur' IS NULL"]
        ]);
        foreach ($usersDB as $user) {
            $user['login'] = $user['user_id'];
            $users[] = User::createFromArray($user);
        }

        return $users;
    }

    /**
     * @param UserInterface $user
     * @param array $values
     * @return void
     * @throws Exception
     */
    public function updateUser(UserInterface $user, array $values): void
    {
        UserModel::update([
            'set'   => $values,
            'where' => ['id = ?'],
            'data'  => [$user->getId()]
        ]);
    }


    /**
     * @param UserInterface $user
     * @return GroupInterface[]
     * @throws Exception
     */
    public function getGroupsById(UserInterface $user): array
    {
        $groups = [];
        $groupsDB = UserModel::getGroupsById(['id' => $user->getId()]);

        foreach ($groupsDB as $group) {
            $groups[] = (new Group())
                ->setGroupId($group['id'])
                ->setLabel($group['group_id'])
                ->setExternalId(json_decode($group['external_id'], true));
        }

        return $groups;
    }

    /**
     * @throws Exception
     */
    public function addSignatorySubstitute(UserInterface $ownerSignatory, UserInterface $signatorySubstitutes): void
    {
        UserModel::update(
            [
                'postSet' => [
                    'signature_substitutes' =>
                        "signature_substitutes || '{$signatorySubstitutes->getId()}'::jsonb"
                ],
                'where'   => ['id = ?'],
                'data'    => [$ownerSignatory->getId()]
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function removeSignatorySubstitute(UserInterface $ownerSignatory, UserInterface $signatorySubstitute): void
    {
        UserModel::update(
            [
                'postSet' => [
                    'signature_substitutes' =>
                        "to_jsonb(array_remove(ARRAY(" .
                        "SELECT jsonb_array_elements_text(signature_substitutes)), '{$signatorySubstitute->getId()}'))"
                ],
                'where'   => ['id = ?'],
                'data'    => [$ownerSignatory->getId()]
            ]
        );
    }

    /**
     * @param int $substituteId
     * @return array
     * @throws Exception
     */
    public function getOwnerIdsBySignatorySubstituteId(int $substituteId): array
    {
        $usersDB = UserModel::get([
            'select' => ['id'],
            'where'  => ["? IN (SELECT jsonb_array_elements_text(signature_substitutes))"],
            'data'   => [(string)$substituteId]
        ]);

        return array_column($usersDB, 'id');
    }


    /**
     * @return UserInterface
     * @throws Exception
     */
    public function getSuperAdmin(): UserInterface
    {
        $user = UserModel::getByLogin(['select' => ['*'], 'login' => 'superadmin']);
        return User::createFromArray($user);
    }

    /**
     * @return UserInterface[]
     * @throws Exception
     */
    public function getRootUsers(): array
    {
        $data = DatabaseModel::select([
            'select' => ['users.*'],
            'table'  => ['users'],
            'where'  => ['mode in (?)', 'status = ?'],
            'data'   => [[UserMode::VISIBLE_ROOT->value, UserMode::INVISIBLE_ROOT->value], 'OK']
        ]);

        return empty($data) ? [] : array_map(fn(array $item) => User::createFromArray($item), $data);
    }

    /**
     * @return UserInterface[]
     * @throws Exception
     */
    public function getAllWebServiceUsers(): array
    {
        $data = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['users'],
            'where'  => ['mode = ?'],
            'data'   => [UserMode::REST->value]
        ]);

        return empty($data) ? [] : array_map(fn(array $item) => User::createFromArray($item), $data);
    }

    /**
     * @param array $proConnect
     * @return UserInterface|array
     * @throws Exception
     */
    public function getUserLinkedWithProConnect(array $proConnect): UserInterface|array
    {
        $usersDB = UserModel::get([
            'select' => ['*'],
            'where'  => ["external_id->>'proConnect' = ?"],
            'data'   => [$proConnect['sub']]
        ]);

        if (count($usersDB) == 1) {
            $usersDB[0]['login'] = $usersDB[0]['user_id'];
            return User::createFromArray($usersDB[0]);
        } else {
            $usersDB = UserModel::get([
                'select' => ['*'],
                'where'  => ["mail = ?", "external_id->>'proConnect' IS NULL"],
                'data'   => [$proConnect['email']]
            ]);
            if (count($usersDB) == 1) {
                $usersDB[0]['login'] = $usersDB[0]['user_id'];
                return ['user' => User::createFromArray($usersDB[0]), 'info' => _NEED_LINK_USER];
            } elseif (count($usersDB) > 1) {
                return ['error' => _NO_UNIQUE_USER_MAIL];
            } else {
                return ['error' => _NO_MATCHING_USER_FOUND];
            }
        }
    }

    /**
     * @throws Exception
     */
    public function linkUserWithProConnect(UserInterface $user, array $userInfos): void
    {
        $currentExternalId = $user->getExternalId() ?? [];

        $currentExternalId['proConnect'] = $userInfos['sub'];

        $this->updateUser($user, ['external_id' => json_encode($currentExternalId)]);
    }

    /**
     * @throws Exception
     */
    public function getUsersFromEntityStringIds(array $entityIds): array
    {
        $usersEntities = DatabaseModel::select([
            'select'    => ['users_entities.user_id as user_id'],
            'table'     => ['users_entities', 'entities'],
            'left_join' => ['users_entities.entity_id = entities.entity_id'],
            'where'     => ['entities.entity_id in (?)'],
            'data'      => $entityIds
        ]);

        $userIds = array_column($usersEntities, 'user_id');

        return $this->getUsersByIds($userIds);
    }

    /**
     * @param string $groupId
     * @return UserInterface[]
     * @throws Exception
     */
    public function getUsersByGroupId(string $groupId): array
    {
        $data = DatabaseModel::select([
            'select' => ['users.*'],
            'table'  => ['usergroups, usergroup_content, users'],
            'where'  => [
                'usergroups.group_id = ?',
                'usergroups.id = usergroup_content.group_id',
                'usergroup_content.user_id = users.id',
                'users.status != ?'
            ],
            'data'   => [$groupId, 'DEL']
        ]);

        return empty($data) ? [] : array_map(fn(array $item) => User::createFromArray($item), $data);
    }

    /**
     * @param string[] $entityStringIds
     * @return array
     * @throws Exception
     */
    public function getUsersByEntityStringIds(array $entityStringIds): array
    {
        $data = DatabaseModel::select([
            'select' => ['DISTINCT(users.*)'],
            'table'  => ['users_entities', 'users'],
            'where'  => [
                'users_entities.entity_id in (?)',
                'users_entities.user_id = users.id'
            ],
            'data'   => [$entityStringIds]
        ]);

        return empty($data) ? [] : array_map(fn(array $item) => User::createFromArray($item), $data);
    }

    /**
     * @throws Exception
     */
    public function getRedirectedUser(
        UserInterface $owner,
        BasketInterface $basket,
        GroupInterface $group
    ): UserInterface|null {
        $data = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['redirected_baskets'],
            'where'  => ['owner_user_id = ?', 'basket_id = ?', 'group_id = ?'],
            'data'   => [$owner->getId(), $basket->getBasketId(), $group->getId()]
        ]);

        return empty($data) ? null : User::createFromArray($data[0]);
    }
}
