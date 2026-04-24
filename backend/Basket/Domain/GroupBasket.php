<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group Basket Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Basket\Domain;

use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\Basket\Port\GroupBasketInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;

class GroupBasket implements GroupBasketInterface
{
    private int $id;
    private GroupInterface $group;
    private BasketInterface $basket;
    private array $listDisplay;
    private string $listEvent;
    private array $listEventData;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getGroup(): GroupInterface
    {
        return $this->group;
    }

    public function setGroup(GroupInterface $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function getBasket(): BasketInterface
    {
        return $this->basket;
    }

    public function setBasket(BasketInterface $basket): self
    {
        $this->basket = $basket;
        return $this;
    }

    public function getListDisplay(): array
    {
        return $this->listDisplay;
    }

    public function setListDisplay(array $listDisplay): self
    {
        $this->listDisplay = $listDisplay;
        return $this;
    }

    public function getListEvent(): string
    {
        return $this->listEvent;
    }

    public function setListEvent(string $listEvent): self
    {
        $this->listEvent = $listEvent;
        return $this;
    }

    public function getListEventData(): array
    {
        return $this->listEventData;
    }

    public function setListEventData(array $listEventData): self
    {
        $this->listEventData = $listEventData;
        return $this;
    }
}
