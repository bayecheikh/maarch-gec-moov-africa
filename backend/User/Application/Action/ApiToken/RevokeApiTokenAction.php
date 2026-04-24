<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Revoke Api Token Action class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\User\Application\Action\ApiToken;

use Exception;
use MaarchCourrier\Core\Domain\Port\DatabaseServiceInterface;
use MaarchCourrier\Core\Domain\User\Port\ApiTokenRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\User\Domain\Port\ApiTokenScheduleServiceInterface;
use MaarchCourrier\User\Domain\Problem\UserTokenNotFoundProblem;

class RevokeApiTokenAction
{
    public function __construct(
        private readonly ApiTokenRepositoryInterface $apiTokenRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly DatabaseServiceInterface $databaseService,
        private readonly ApiTokenScheduleServiceInterface $apiTokenScheduleService
    ) {
    }

    /**
     * @throws UserDoesNotExistProblem
     * @throws UserTokenNotFoundProblem
     * @throws Exception
     */
    public function execute(int $userId): void
    {
        $user = $this->userRepository->getUserById($userId);
        if ($user === null) {
            throw new UserDoesNotExistProblem($userId);
        }

        $token = $this->apiTokenRepository->getByUser($user);
        if ($token === null) {
            throw new UserTokenNotFoundProblem($userId);
        }

        $this->databaseService->beginTransaction();
        $this->apiTokenRepository->delete($token);

        $count = count($this->apiTokenRepository->getAllTokens());

        if ($count == 0 && $this->apiTokenScheduleService->doesNotifAlert()) {
            try {
                $this->apiTokenScheduleService->deleteNotifAlert();
            } catch (Exception $e) {
                $this->databaseService->rollbackTransaction();
                throw $e;
            }
        }

        $this->databaseService->commitTransaction();
    }
}
