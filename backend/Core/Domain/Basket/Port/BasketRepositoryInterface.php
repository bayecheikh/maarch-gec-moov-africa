<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Basket Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Basket\Port;

interface BasketRepositoryInterface
{
    public function getBasketById(int $id): BasketInterface|null;

    public function getNotifiableBasket(): array;
}
