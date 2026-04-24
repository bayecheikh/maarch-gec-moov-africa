<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Basket Clause Service class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Basket\Infrastructure\Service;

use Exception;
use MaarchCourrier\Core\Domain\Basket\Port\BasketClauseServiceInterface;
use MaarchCourrier\Core\Domain\Basket\Port\BasketInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use SrcCore\controllers\PreparedClauseController;

class BasketClauseService implements BasketClauseServiceInterface
{
    /**
     * @param BasketInterface $basket
     * @param UserInterface $user
     *
     * @return string
     * @throws Exception
     */
    public function prepare(BasketInterface $basket, UserInterface $user): string
    {
        return PreparedClauseController::getPreparedClause([
            'clause' => $basket->getClause(),
            'userId' => $user->getId()
        ]);
    }
}
