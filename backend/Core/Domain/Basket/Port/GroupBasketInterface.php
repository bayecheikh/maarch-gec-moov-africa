<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Basket Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Basket\Port;

use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;

interface GroupBasketInterface
{
    public function getId(): int;
    public function setId(int $id): self;
    public function getGroup(): GroupInterface;
    public function setGroup(GroupInterface $group): self;
    public function getBasket(): BasketInterface;
    public function setBasket(BasketInterface $basket): self;
    public function getListDisplay(): array;
    public function setListDisplay(array $listDisplay): self;
    public function getListEvent(): string;
    public function setListEvent(string $listEvent): self;
    public function getListEventData(): array;
    public function setListEventData(array $listEventData): self;
}
