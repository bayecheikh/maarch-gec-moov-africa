<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurrentUserRepository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Infrastructure;

use Exception;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\User\Domain\User;
use SrcCore\controllers\AuthenticationController;
use SrcCore\controllers\CoreController;
use User\models\UserModel;

class CurrentUserInformations implements CurrentUserInterface
{
    /**
     * @throws Exception
     */
    public function getCurrentUser(): UserInterface
    {
        $user = UserModel::getById(['id' => $GLOBALS['id']]);
        return User::createFromArray([
            'id'        => $GLOBALS['id'],
            'login'     => $user['user_id'],
            'firstname' => $user['firstname'],
            'lastname'  => $user['lastname'],
            'mail'      => $user['mail'],
            'phone'     => $user['phone']
        ]);
    }

    public function getCurrentUserId(): int
    {
        return $GLOBALS['id'];
    }

    public function getCurrentUserLogin(): string
    {
        return $GLOBALS['login'];
    }

    /**
     * @return string
     */
    public function getCurrentUserToken(): string
    {
        return $GLOBALS['token'];
    }

    /**
     * @throws Exception
     */
    public function generateNewToken(): string
    {
        return AuthenticationController::getJWT();
    }

    /**
     * @throws Exception
     */
    public function setCurrentUser(int $userId): void
    {
        CoreController::setGlobals(['userId' => $userId]);
    }
}
