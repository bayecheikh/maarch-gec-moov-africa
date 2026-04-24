<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Link Token
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Application\MaarchMobile;

use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\ExternalExport\Domain\MaarchMobile\Problem\CannotLinkAnOtherUserMobileInformationProblem;
use MaarchCourrier\History\Application\AddHistoryRecord;
use Psr\Log\LoggerInterface;

class LinkToken
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AddHistoryRecord $addHistoryRecord,
        private readonly UserRepositoryInterface $userRepository,
        private readonly CurrentUserInterface $currentUser
    ) {
    }

    /**
     * @param int $userId
     * @param array $tokens
     * @param array $preferenceNotifMCM
     * @return void
     * @throws UserDoesNotExistProblem
     * @throws CannotLinkAnOtherUserMobileInformationProblem
     */
    public function execute(int $userId, array $tokens, array $preferenceNotifMCM): void
    {
        $user = $this->userRepository->getUserById($userId);
        if ($user === null) {
            throw new UserDoesNotExistProblem($userId);
        } elseif ($user->getId() !== $this->currentUser->getCurrentUserId()) {
            throw new CannotLinkAnOtherUserMobileInformationProblem($user->getId());
        }

        $externalId = $user->getExternalId();

        if (!empty($tokens)) {
            foreach ($tokens as $token) {
                if (!isset($externalId['tokenMCM']) || !in_array($token, $externalId['tokenMCM'])) {
                    $externalId['tokenMCM'][] = $token;
                }
            }
        }

        if (!empty($preferenceNotifMCM)) {
            $externalId['preferenceNotifMCM'] = $preferenceNotifMCM;
        }

        if (!empty($externalId)) {
            $this->userRepository->updateUser($user, ['external_id' => json_encode($externalId)]);
            $this->logger->debug(
                'Update user with external',
                ['userId' => $user->getId(), 'externalId' => $externalId]
            );
        }

        $info = "User linked to Maarch Courrier Mobile with tokens and preferences";

        $this->addHistoryRecord->add(
            tableName: 'users',
            recordId: (string)$user->getId(),
            eventId: 'userUpdate',
            eventType: 'UP',
            info: $info,
            user: $user
        );
    }
}
