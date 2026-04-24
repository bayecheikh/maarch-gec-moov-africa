<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Create Api Token Action class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\User\Application\Action\ApiToken;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\Core\Domain\Port\DatabaseServiceInterface;
use MaarchCourrier\Core\Domain\User\Port\ApiTokenRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\Core\Domain\User\UserMode;
use MaarchCourrier\User\Domain\ApiToken;
use MaarchCourrier\User\Domain\Port\ApiTokenScheduleServiceInterface;
use MaarchCourrier\User\Domain\Problem\ExpirationDateMustBeInTheFutureProblem;
use MaarchCourrier\User\Domain\Problem\TokenExpirationDateExceedProblem;
use MaarchCourrier\User\Domain\Problem\UserHaveAlreadyTokenProblem;
use MaarchCourrier\User\Domain\Problem\UserIsNotInRestModeProblem;

class CreateApiTokenAction
{
    public function __construct(
        private readonly ApiTokenRepositoryInterface $apiTokenRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly DatabaseServiceInterface $databaseService,
        private readonly ApiTokenScheduleServiceInterface $apiTokenScheduleService
    ) {
    }

    /**
     * @param int $userId
     * @param DateTimeImmutable $expirationDate
     *
     * @return ApiToken
     * @throws ExpirationDateMustBeInTheFutureProblem
     * @throws TokenExpirationDateExceedProblem
     * @throws UserDoesNotExistProblem
     * @throws UserHaveAlreadyTokenProblem
     * @throws UserIsNotInRestModeProblem
     * @throws Exception
     */
    public function execute(int $userId, DateTimeImmutable $expirationDate): ApiToken
    {
        $user = $this->userRepository->getUserById($userId);
        if ($user === null) {
            throw new UserDoesNotExistProblem($userId);
        }

        if ($user->getMode() != UserMode::REST) {
            throw new UserIsNotInRestModeProblem($userId);
        }

        if ($expirationDate < new DateTimeImmutable()) {
            throw new ExpirationDateMustBeInTheFutureProblem();
        }

        $maxDate = new DateTimeImmutable('+1 year');
        if ($expirationDate > $maxDate) {
            throw new TokenExpirationDateExceedProblem();
        }

        $existingToken = $this->apiTokenRepository->getByUser($user);
        if ($existingToken !== null && !$existingToken->isExpired()) {
            throw new UserHaveAlreadyTokenProblem();
        }

        $token = new ApiToken();
        $token->setUser($user)
            ->setToken($this->apiTokenRepository->generateToken($userId, new DateTimeImmutable(), $expirationDate))
            ->setCreationDate(new DateTimeImmutable())
            ->setExpirationDate($expirationDate);

        $this->databaseService->beginTransaction();
        $this->apiTokenRepository->save($token);

        $count = count($this->apiTokenRepository->getAllTokens());

        if ($count >= 1 && !$this->apiTokenScheduleService->doesNotifAlert()) {
            try {
                $this->apiTokenScheduleService->createNotifAlert();
            } catch (Exception $e) {
                $this->databaseService->rollbackTransaction();
                throw $e;
            }
        }

        $this->databaseService->commitTransaction();
        return $token;
    }
}
