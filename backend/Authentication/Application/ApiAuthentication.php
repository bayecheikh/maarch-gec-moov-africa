<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Api Authentication class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Application;

use MaarchCourrier\Authentication\Domain\AuthenticateServiceInterface;
use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\TokenServiceInterface;
use MaarchCourrier\Core\Domain\User\Port\ApiTokenRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\UserMode;

class ApiAuthentication
{
    private bool $isBasicAuthAllowed;
    private array $serverVariables;

    public function __construct(
        public readonly EnvironmentInterface $environment,
        public readonly UserRepositoryInterface $userRepository,
        public readonly AuthenticateServiceInterface $authenticateService,
        public readonly TokenServiceInterface $tokenService,
        public readonly ApiTokenRepositoryInterface $apiTokenRepository
    ) {
    }

    public function isBasicAuthAllowed(): bool
    {
        return $this->isBasicAuthAllowed;
    }

    private function canUseBasicAuth(): bool
    {
        if (!empty($this->serverVariables['PHP_AUTH_USER']) && !empty($this->serverVariables['PHP_AUTH_PW'])) {
            $user = $this->userRepository->getByLogin($this->serverVariables['PHP_AUTH_USER']);

            if ($user !== null) {
                $userApiToken = $this->apiTokenRepository->getByUser($user);

                if (
                    ($user->getMode() != UserMode::REST && $this->environment->getLoggingMethod() != 'standard') ||
                    ($user->getMode() == UserMode::REST && $userApiToken !== null && !empty($userApiToken->getToken()))
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    public function execute(array $authorizationHeaders = []): ?UserInterface
    {
        $user = null;
        $this->serverVariables = $this->environment->getServerVariables();
        $this->isBasicAuthAllowed = $this->canUseBasicAuth();

        if (
            !empty($this->serverVariables['PHP_AUTH_USER']) &&
            !empty($this->serverVariables['PHP_AUTH_PW']) &&
            $this->isBasicAuthAllowed
        ) {
            if (
                $this->authenticateService->authentication(
                    $this->serverVariables['PHP_AUTH_USER'],
                    $this->serverVariables['PHP_AUTH_PW']
                )
            ) {
                $user = $this->userRepository->getByLogin($this->serverVariables['PHP_AUTH_USER']);
            }
        } elseif (!empty($authorizationHeaders)) {
            $token = null;
            foreach ($authorizationHeaders as $authorizationHeader) {
                if (str_starts_with($authorizationHeader, 'Bearer')) {
                    $token = (string)str_replace('Bearer ', '', $authorizationHeader);
                    break;
                }
            }

            if (!empty($token)) {
                $authTokenValidator = new AuthTokenValidator(
                    $this->userRepository,
                    $this->tokenService,
                    $this->apiTokenRepository
                );

                $user = $authTokenValidator->validate($token);

                $this->environment->setGlobalVariable('token', $token);
            }
        }

        if ($user != null) {
            $this->userRepository->updateUser($user, ['reset_token' => null]);

            $userApiToken = $this->apiTokenRepository->getByUser($user);
            if ($userApiToken != null) {
                $this->apiTokenRepository->updateLastUsedDate($userApiToken);
            }
        }

        return $user;
    }
}
