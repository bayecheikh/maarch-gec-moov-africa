<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ProConnect Authentication class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Application\ProConnect;

use MaarchCourrier\Authentication\Domain\ProConnect\Port\ProConnectApiServiceInterface;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\AuthorizationCodeIsEmptyProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\NoMatchingUserFoundProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\NonceParameterIsEmptyProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\NoUniqueUserMailProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\ProConnectConfigNotFoundProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\ProConnectInvalidTokenProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\ProConnectIsDisabledProblem;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

class ProConnectAuthentication
{
    private ?string $idToken;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ProConnectConfiguration $proConnectConfiguration,
        private readonly ProConnectApiServiceInterface $proConnectApiService,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * @param string|null $authorizationCode
     * @param string|null $nonce
     * @return UserInterface|null
     * @throws AuthorizationCodeIsEmptyProblem
     * @throws NoMatchingUserFoundProblem
     * @throws NoUniqueUserMailProblem
     * @throws NonceParameterIsEmptyProblem
     * @throws ProConnectConfigNotFoundProblem
     * @throws ProConnectInvalidTokenProblem
     * @throws ProConnectIsDisabledProblem
     */
    public function execute(?string $authorizationCode, ?string $nonce): ?UserInterface
    {
        $this->logger->info('Starting ProConnect authentication process');

        if (empty($authorizationCode)) {
            throw new AuthorizationCodeIsEmptyProblem();
        }

        if (empty($nonce)) {
            throw new NonceParameterIsEmptyProblem();
        }

        $proConnectConfigurationValues = $this->proConnectConfiguration->getProConnectConfiguration();
        $this->proConnectApiService->setConfig(
            $proConnectConfigurationValues,
            $authorizationCode,
            $nonce
        );

        $this->proConnectApiService->generateToken();

        if ($this->proConnectApiService->isTokenValid()) {
            $userInfos = $this->proConnectApiService->getUserInfos();
            $user = $this->userRepository->getUserLinkedWithProConnect($userInfos);
            if ($user instanceof UserInterface) {
                $this->idToken = $this->proConnectApiService->getIdToken();
                return $user;
            } elseif (!empty($user['error']) && str_contains($user['error'], _NO_MATCHING_USER_FOUND)) {
                throw new NoMatchingUserFoundProblem();
            } elseif (!empty($user['error']) && str_contains($user['error'], _NO_UNIQUE_USER_MAIL)) {
                throw new NoUniqueUserMailProblem();
            } elseif (str_contains($user['info'], _NEED_LINK_USER)) {
                $this->userRepository->linkUserWithProConnect($user['user'], $userInfos);
                $this->idToken = $this->proConnectApiService->getIdToken();
                return $user['user'];
            }
        } else {
            throw new ProConnectInvalidTokenProblem();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getIdToken(): ?string
    {
        return $this->idToken;
    }
}
