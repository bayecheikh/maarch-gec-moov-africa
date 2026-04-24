<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Redirect Basket Repository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Basket\Infrastructure\Repository;

use Exception;
use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\Basket\Port\RedirectBasketRepositoryInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use SrcCore\models\DatabaseModel;

class RedirectBasketRepository implements RedirectBasketRepositoryInterface
{
    /**
     * @param UserInterface $basketOwner
     * @param UserInterface $connectedUser
     * @param GroupInterface $group
     * @param BasketInterface $basket
     * @return bool
     * @throws Exception
     */
    public function isBasketAssignedToUserOfGroup(
        UserInterface $basketOwner,
        UserInterface $connectedUser,
        GroupInterface $group,
        BasketInterface $basket
    ): bool {
        $basket = DatabaseModel::select([
            'select'   => [
                'rb.id',
                'rb.actual_user_id',
                'rb.owner_user_id',
                'rb.group_id',
                'ba.id as basket_id'
            ],
            'table'    => ['baskets ba, redirected_baskets rb'],
            'where'    => [
                'rb.owner_user_id = ?',
                'rb.actual_user_id = ?',
                'rb.group_id = ?',
                'ba.id = ?',
                'rb.basket_id = ba.basket_id'
            ],
            'data'     => [$basketOwner->getId(), $connectedUser->getId(), $group->getId(), $basket->getId()],
            'order_by' => ['ba.basket_order, ba.basket_name']
        ]);

        return !empty($basket);
    }

    /**
     * @param UserInterface $basketOwner
     * @return array
     * @throws Exception
     */
    public function getRedirectedBasketsByUser(UserInterface $basketOwner): array
    {
        return DatabaseModel::select([
            'select'   => [
                'rb.id',
                'rb.actual_user_id',
                'rb.group_id'
            ],
            'table'    => ['baskets ba, redirected_baskets rb, usergroups'],
            'where'    => ['rb.owner_user_id = ?', 'rb.basket_id = ba.basket_id', 'usergroups.id = rb.group_id'],
            'data'     => [$basketOwner->getId()],
            'order_by' => ['rb.id']
        ]);
    }
}
