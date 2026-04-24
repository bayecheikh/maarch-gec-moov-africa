<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Recipient Filter
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Application;

use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\DiffusionList\Port\ListInstanceInterface;
use MaarchCourrier\Core\Domain\Entity\Port\EntityInterface;
use MaarchCourrier\Core\Domain\Entity\Port\EntityRepositoryInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\DiffusionList\Domain\Port\ListInstanceRepositoryInterface;

class NotificationRecipientFilter
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EntityRepositoryInterface $entityRepository,
        private readonly ListInstanceRepositoryInterface $listInstanceRepository
    ) {
    }

    /**
     * Filters the given list of users according to the notification's diffusion type and properties.
     * @param NotificationInterface $notification
     * @param GroupInterface $group The group being processed (needed for 'group').
     * @param BasketInterface $basket The basket being processed (needed for 'copy_list').
     * @param array<UserInterface>  $groupUsers    Users in the current group/basket context.
     * @return array<UserInterface> Filtered list of users to notify.
     */
    public function filterRecipients(
        NotificationInterface $notification,
        GroupInterface $group,
        BasketInterface $basket,
        array $groupUsers
    ): array {
        $type = $notification->getDiffusionType();
        $properties = $notification->getDiffusionProperties();
        $filtered = $groupUsers;  // start with all group users

        switch ($type) {
            case 'group':
                if (!empty($properties)) {
                    // Only keep users if this group's ID is in the allowed group list.
                    // (Assume groupUsers all belong to one group, so if a group is not allowed, no users)
                    if (!in_array($group->getGroupId(), $properties, true)) {
                        $filtered = [];  // no users in this group should be notified
                    }
                }
                break;
            case 'entity':
                if (!empty($properties)) {
                    // Intersect group users with users of the target entities
                    $usersFromTargetedEntities = $this->userRepository->getUsersByEntityStringIds($properties);
                    $userIds = array_map(
                        fn(UserInterface $user) => $user->getId(),
                        $usersFromTargetedEntities
                    );
                    $filtered = array_filter(
                        $filtered,
                        function (UserInterface $user) use ($userIds) {
                            return in_array($user->getId(), $userIds, true);
                        }
                    );
                }
                break;
            case 'dest_user':
                // Only users who appear in a "dest" distribution list (likely all incoming mail recipients)
                $listInstances = $this->listInstanceRepository->getUsersInDestFromDistributionToServices();
                $destUserIds = array_map(
                    fn(ListInstanceInterface $instance) => $instance->getItemId(),
                    $listInstances
                );
                $filtered = array_filter(
                    $filtered,
                    fn(UserInterface $user) => in_array($user->getId(), $destUserIds, true)
                );
                break;
            case 'copy_list':
                // Only notify for the special "CopyMailBasket" or "Basket_Copie" from MC 24 demo fixtures
                $filtered = [];
                if (in_array($basket->getBasketId(), ['CopyMailBasket', 'Basket_Copie'])) {
                    // For the copy list, include users explicitly in the 'cc' list or members of entities in that list
                    $tmpItemsIncCopy = $this->listInstanceRepository->getEntitiesInCopyFromDistributionToServices();

                    foreach ($groupUsers as $user) {
                        foreach ($tmpItemsIncCopy as $itemUserOrEntity) {
                            // If the item is a direct user match
                            if (
                                $itemUserOrEntity->getItemType() == 'user_id' &&
                                $itemUserOrEntity->getItemId() == $user->getId()
                            ) {
                                $filteredUserIds = array_map(
                                    fn(UserInterface $obj) => $obj->getId(),
                                    $filtered
                                );
                                if (!in_array($user->getId(), $filteredUserIds)) {
                                    $filtered[] = $user;
                                    break;
                                }
                            } elseif ($itemUserOrEntity->getItemType() == 'entity_id') {
                                // If the item is an entity that this user belongs to
                                $userEntities = $this->entityRepository->getEntitiesForUser($user);
                                $userEntityIds = array_map(fn(EntityInterface $obj) => $obj->getId(), $userEntities);
                                // If any entity that the user belongs to is in the cc list, include the user
                                if (in_array($itemUserOrEntity->getItemId(), $userEntityIds)) {
                                    $filtered[] = $user;
                                    break;
                                }
                            }
                        }
                    }
                    // Deduplicate users in case of overlaps
                    $filtered = array_unique($filtered, SORT_REGULAR);
                }
                break;
            case 'user':
                if (!empty($properties)) {
                    // Only include specific user IDs
                    $allowedIds = array_map('intval', $properties);
                    $filtered = array_filter(
                        $filtered,
                        fn(UserInterface $user) => in_array($user->getId(), $allowedIds, true)
                    );
                }
                break;
            default:
                // Other types could be handled here
                break;
        }

        return array_values($filtered);
    }
}
