<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Get Api Token Action Factory class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\User\Infrastructure\Factory;

use MaarchCourrier\User\Application\Action\ApiToken\GetApiTokenAction;
use MaarchCourrier\User\Infrastructure\Repository\ApiTokenRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;

class GetApiTokenActionFactory
{
    public function getApiTokenAction(): getApiTokenAction
    {
        $userRepository = new UserRepository();
        $tokenRepository = new ApiTokenRepository($userRepository);
        return new getApiTokenAction($tokenRepository, $userRepository);
    }
}
