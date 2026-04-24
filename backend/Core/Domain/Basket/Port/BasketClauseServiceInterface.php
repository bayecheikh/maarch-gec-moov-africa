<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Prepared Basket Clause Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Basket\Port;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface BasketClauseServiceInterface
{
    public function prepare(BasketInterface $basket, UserInterface $user): string;
}
