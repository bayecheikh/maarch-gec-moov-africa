<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Basket Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Basket\Port;

interface BasketInterface
{
    public function getId(): int;

    public function setId(int $id): BasketInterface;

    public function getClause(): string;

    public function setClause(string $clause): BasketInterface;

    public function getBasketId(): string;

    public function setBasketId(string $basketId): BasketInterface;

    public function getName(): string;

    public function setName(string $name): BasketInterface;
}
