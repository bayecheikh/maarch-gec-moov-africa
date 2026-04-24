<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Update Last Use Action Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Infrastructure\Factory;

use MaarchCourrier\User\Application\Action\ApiToken\UpdateLastUseAction;
use MaarchCourrier\User\Infrastructure\Repository\ApiTokenRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;

class UpdateLastUseActionFactory
{
    public function updateLastUseAction(): UpdateLastUseAction
    {
        $userRepository = new UserRepository();
        $tokenRepository = new ApiTokenRepository($userRepository);
        return new UpdateLastUseAction($tokenRepository, $userRepository);
    }
}
