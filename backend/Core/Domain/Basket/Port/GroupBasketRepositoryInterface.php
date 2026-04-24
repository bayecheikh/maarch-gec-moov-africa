<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Basket Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Basket\Port;

interface GroupBasketRepositoryInterface
{
    public function getGroupBasket(int $groupId, int $basketId): GroupBasketInterface|null;
}
