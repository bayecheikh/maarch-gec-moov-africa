<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Basket Repository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Basket\Infrastructure\Repository;

use Basket\models\BasketModel;
use Exception;
use MaarchCourrier\Basket\Domain\Basket;
use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\Basket\Port\BasketRepositoryInterface;

class BasketRepository implements BasketRepositoryInterface
{
    /**
     * Create a ListInstance object from an array
     */
    private function createBasketFromData(array $data): BasketInterface
    {
        return (new Basket())
            ->setId($data['id'])
            ->setClause($data['basket_clause'])
            ->setBasketId($data['basket_id'])
            ->setName($data['basket_name']);
    }

    /**
     * @param int $id
     *
     * @return BasketInterface|null
     * @throws Exception
     */
    public function getBasketById(int $id): BasketInterface|null
    {
        $basket = BasketModel::get([
            'select' => ['*'],
            'where'  => ['id = ?'],
            'data'   => [$id]
        ]);

        return !empty($basket) ? $this->createBasketFromData($basket[0]) : null;
    }

    /**
     * @return BasketInterface[]
     * @throws Exception
     */
    public function getNotifiableBasket(): array
    {
        $baskets = BasketModel::get([
            'select' => ['*'],
            'where'  => ['flag_notif = ?'],
            'data'   => ['Y']
        ]);

        return array_map(fn($basket) => $this->createBasketFromData($basket), $baskets);
    }
}
