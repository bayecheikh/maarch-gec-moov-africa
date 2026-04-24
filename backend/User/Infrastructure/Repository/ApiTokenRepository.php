<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Api Token Repository class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\User\Infrastructure\Repository;

use DateTimeImmutable;
use Exception;
use Firebase\JWT\JWT;
use MaarchCourrier\Core\Domain\User\Port\ApiTokenRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\User\Domain\ApiToken;
use MaarchCourrier\User\Domain\Port\ApiTokenInterface;
use SrcCore\models\CoreConfigModel;

class ApiTokenRepository implements ApiTokenRepositoryInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * @throws Exception
     */
    private function createApiToken(UserInterface $user): ApiToken
    {
        $token = new ApiToken();
        $token->setUser($user)
            ->setToken($user->getLongTimeToken()['token'])
            ->setCreationDate(new DateTimeImmutable($user->getLongTimeToken()['creation_date']))
            ->setExpirationDate(new DateTimeImmutable($user->getLongTimeToken()['expiration_date']));

        if (!empty($user->getLongTimeToken()['last_used_date'])) {
            $token->setLastUsedDate(new DateTimeImmutable($user->getLongTimeToken()['last_used_date']));
        }

        return $token;
    }

    /**
     * Retrieves an API token by user.
     *
     * @param UserInterface $user The user object
     * @return ?ApiToken Returns an ApiToken object or null if token not found
     * @throws Exception
     */
    public function getByUser(UserInterface $user): ?ApiToken
    {
        $user = $this->userRepository->getUserById($user->getId());

        if (
            empty($user->getLongTimeToken()) || empty($user->getLongTimeToken()['token']) ||
            empty($user->getLongTimeToken()['creation_date']) || empty($user->getLongTimeToken()['expiration_date'])
        ) {
            return null;
        }

        return $this->createApiToken($user);
    }

    /**
     * @return ApiTokenInterface[]
     * @throws Exception
     */
    public function getAllTokens(): array
    {
        $apiTokens = [];
        $users = $this->userRepository->getAllWebServiceUsers();

        foreach ($users as $user) {
            if (
                empty($user->getLongTimeToken()) || empty($user->getLongTimeToken()['token']) ||
                empty($user->getLongTimeToken()['creation_date']) || empty($user->getLongTimeToken()['expiration_date'])
            ) {
                continue;
            }

            $apiTokens[] = $this->createApiToken($user);
        }

        return $apiTokens;
    }

    /**
     * @throws Exception
     */
    public function save(ApiTokenInterface $token): void
    {
        $this->userRepository->updateUser(
            $token->getUser(),
            ['long_time_token' => json_encode($token->toArray())]
        );
    }

    /**
     * @throws Exception
     */
    public function delete(ApiTokenInterface $token): void
    {
        $this->userRepository->updateUser(
            $token->getUser(),
            ['long_time_token' => '[]']
        );
    }

    /**
     * @throws Exception
     */
    public function updateLastUsedDate(ApiTokenInterface $token): void
    {
        $token->setLastUsedDate(new DateTimeImmutable());
        $this->save($token);
    }

    /**
     * @throws Exception
     */
    public function generateToken(
        int $userId,
        DateTimeImmutable $creationDate,
        DateTimeImmutable $expirationDate
    ): string {
        $user = $this->userRepository->getUserById($userId);

        if (empty($user)) {
            throw new Exception('User not found');
        }

        $token = [
            'iat' => $creationDate->getTimestamp(),
            'exp' => $expirationDate->getTimestamp(),
            'sub' => [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'status' => $user->getStatus(),
                'login' => $user->getLogin()
            ]
        ];

        return JWT::encode($token, CoreConfigModel::getEncryptKey(), 'HS256');
    }
}
