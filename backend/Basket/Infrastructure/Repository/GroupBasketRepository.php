<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Basket Repository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Basket\Infrastructure\Repository;

use Basket\models\GroupBasketModel;
use Exception;
use MaarchCourrier\Basket\Domain\GroupBasket;
use MaarchCourrier\Core\Domain\Basket\Port\BasketRepositoryInterface;
use MaarchCourrier\Core\Domain\Basket\Port\GroupBasketInterface;
use MaarchCourrier\Core\Domain\Basket\Port\GroupBasketRepositoryInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupRepositoryInterface;

class GroupBasketRepository implements GroupBasketRepositoryInterface
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly BasketRepositoryInterface $basketRepository
    ) {
    }

    /**
     * @param  int  $groupId
     * @param  int  $basketId
     * @return GroupBasketInterface|null
     * @throws Exception
     */
    public function getGroupBasket(int $groupId, int $basketId): GroupBasketInterface|null
    {
        $basket = $this->basketRepository->getBasketById($basketId);
        $group = $this->groupRepository->getById($groupId);

        if (empty($basket) || empty($group)) {
            return null;
        }

        $groupBasket = GroupBasketModel::get([
            'select' => ['*'],
            'where'  => ['group_id = ?', 'basket_id = ?'],
            'data'   => [$group->getGroupId(), $basket->getBasketId()]
        ]);
        if (empty($groupBasket)) {
            return null;
        }

        return (new GroupBasket())
            ->setId($groupBasket[0]['id'])
            ->setBasket($basket)
            ->setGroup($group)
            ->setListDisplay(json_decode($groupBasket[0]['list_display'], true))
            ->setListEvent($groupBasket[0]['list_event'])
            ->setListEventData(json_decode($groupBasket[0]['list_event_data'], true));
    }
}
