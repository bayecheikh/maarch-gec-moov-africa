<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Auth Token Validator class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Application;

use MaarchCourrier\Core\Domain\TokenServiceInterface;
use MaarchCourrier\Core\Domain\User\Port\ApiTokenRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;

class AuthTokenValidator
{
    public function __construct(
        public readonly UserRepositoryInterface $userRepository,
        public readonly TokenServiceInterface $tokenService,
        public readonly ApiTokenRepositoryInterface $apiTokenRepository
    ) {
    }

    public function validate(string $token): ?UserInterface
    {
        $decodedToken = $this->tokenService->decode($token);
        if ($decodedToken == null) {
            return null;
        }

        $userId = match (true) {
            // Legacy token: user id exists and sub id is not set.
            !empty($decodedToken?->user?->id) && !isset($decodedToken?->sub?->id) => (int)$decodedToken->user->id,

            // Webservice token: sub id exists and user is not set.
            !empty($decodedToken?->sub?->id) && !isset($decodedToken->user) => (int)$decodedToken->sub->id,

            default => null,
        };

        if (!empty($userId)) {
            $user = $this->userRepository->getUserById($userId);
            if (!empty($decodedToken?->sub?->id)) {
                $currentTokenUser = $this->apiTokenRepository->getByUser($user);
                if (empty($currentTokenUser) || $token !== $currentTokenUser->getToken()) {
                    $user = null;
                }
            }
        }

        return $user ?? null;
    }
}
