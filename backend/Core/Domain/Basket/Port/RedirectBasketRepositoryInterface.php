<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Redirect Basket Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Basket\Port;

use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface RedirectBasketRepositoryInterface
{
    public function isBasketAssignedToUserOfGroup(
        UserInterface $basketOwner,
        UserInterface $connectedUser,
        GroupInterface $group,
        BasketInterface $basket
    ): bool;

    public function getRedirectedBasketsByUser(UserInterface $basketOwner): array;
}
