<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Revoke Api Token Action Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Infrastructure\Factory;

use MaarchCourrier\Core\Infrastructure\Database\DatabaseService;
use MaarchCourrier\User\Application\Action\ApiToken\RevokeApiTokenAction;
use MaarchCourrier\User\Infrastructure\ApiTokenScheduleService;
use MaarchCourrier\User\Infrastructure\Repository\ApiTokenRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;

class RevokeApiTokenActionFactory
{
    public function revokeApiTokenAction(): RevokeApiTokenAction
    {
        $userRepository = new UserRepository();
        $tokenRepository = new ApiTokenRepository($userRepository);
        return new RevokeApiTokenAction(
            $tokenRepository,
            $userRepository,
            new DatabaseService(),
            new ApiTokenScheduleService()
        );
    }
}
