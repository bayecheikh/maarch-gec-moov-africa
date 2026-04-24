<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Unlink Preference Notification
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Application\MaarchMobile;

use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\ExternalExport\Domain\MaarchMobile\Problem\CannotUnlinkAnOtherUserMobileInformationProblem;
use MaarchCourrier\History\Application\AddHistoryRecord;
use Psr\Log\LoggerInterface;

class UnlinkPreferenceNotification
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
     * @return void
     * @throws UserDoesNotExistProblem
     * @throws CannotUnlinkAnOtherUserMobileInformationProblem
     */
    public function execute(int $userId): void
    {
        $user = $this->userRepository->getUserById($userId);
        if ($user === null) {
            throw new UserDoesNotExistProblem($userId);
        } elseif ($user->getId() !== $this->currentUser->getCurrentUserId()) {
            throw new CannotUnlinkAnOtherUserMobileInformationProblem($user->getId());
        }

        $externalId = $user->getExternalId();

        if (isset($externalId['preferenceNotifMCM'])) {
            unset($externalId['preferenceNotifMCM']);

            $this->userRepository->updateUser($user, ['external_id' => json_encode($externalId)]);
            $this->logger->debug(
                'User notification preferences unlinked successfully',
                ['userId' => $user->getId(), 'externalId' => $externalId]
            );

            $this->addHistoryRecord->add(
                tableName: 'users',
                recordId: (string)$user->getId(),
                eventId: 'userUpdate',
                eventType: 'DEL',
                info: "User notification preferences unlinked from Maarch Courrier Mobile",
                user: $user
            );
        }
    }
}
