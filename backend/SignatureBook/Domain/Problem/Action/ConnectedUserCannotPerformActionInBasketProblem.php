<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Connected User Cannot Perform Action In Basket Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem\Action;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

class ConnectedUserCannotPerformActionInBasketProblem extends Problem
{
    public function __construct(
        MainResourceInterface $mainResource,
        UserInterface $user,
        UserInterface $basketOwner,
        int $groupId,
        int $basketId
    ) {
        $msg = "You cannot perform this action ";

        if ($user->getId() === $basketOwner->getId()) {
            $msg .= "in your basket '$basketId' of group '$groupId'";
        } else {
            $msg .= "on behalf of user '{$basketOwner->getId()}' in his basket '$basketId' of group '$groupId'";
        }

        parent::__construct(
            $msg,
            403,
            [
                'mainResourceId'     => $mainResource->getResId(),
                'connectedUserId'   => $user->getId(),
                'basketOwnerId'     => $basketOwner->getId(),
                'basketId'        => $basketId,
                'groupIdOfBasket' => $groupId
            ],
            'userCannotPerformAction'
        );
    }
}
