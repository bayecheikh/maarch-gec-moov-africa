<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Get Api Token Action class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\User\Application\Action\ApiToken;

use MaarchCourrier\Core\Domain\User\Port\ApiTokenRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\User\Domain\Port\ApiTokenInterface;

class GetApiTokenAction
{
    public function __construct(
        private readonly ApiTokenRepositoryInterface $apiTokenRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }


    /**
     * @throws UserDoesNotExistProblem
     */
    public function execute(int $userId): ?ApiTokenInterface
    {
        $user = $this->userRepository->getUserById($userId);
        if ($user === null) {
            throw new UserDoesNotExistProblem($userId);
        }

        return $this->apiTokenRepository->getByUser($user);
    }
}
