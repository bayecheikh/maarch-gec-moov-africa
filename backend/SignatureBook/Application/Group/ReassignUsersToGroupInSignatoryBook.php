<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Reassign Users To Group In Signatory Book
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Group;

use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookUserServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\AddUserToAGroupInSignatoryBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\GroupExternalIdNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserExternalIdNotFoundProblem;

class ReassignUsersToGroupInSignatoryBook
{
    public function __construct(
        private readonly SignatureBookUserServiceInterface $signatureBookUserService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceJsonConfigLoader,
    ) {
    }


    /**
     * @param GroupInterface  $group
     * @param UserInterface[] $users
     *
     * @return GroupInterface
     * @throws SignatureBookNoConfigFoundProblem*@throws GroupExternalIdNotFoundProblem
     * @throws UserExternalIdNotFoundProblem|GroupExternalIdNotFoundProblem
     * @throws AddUserToAGroupInSignatoryBookFailedProblem
     */
    public function reassignUsers(GroupInterface $group, array $users): GroupInterface
    {
        $signatureBook = $this->signatureServiceJsonConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->signatureBookUserService->setConfig($signatureBook);

        $groupExternalId = $group->getExternalId();

        if (empty($groupExternalId['internalParapheur'])) {
            throw new GroupExternalIdNotFoundProblem();
        } else {
            foreach ($users as $user) {
                $userExternalId = $user->getExternalId();
                if (empty($userExternalId['internalParapheur'])) {
                    throw new UserExternalIdNotFoundProblem();
                }
            }
            foreach ($users as $user) {
                $isUserAddedToGroup = $this->signatureBookUserService->addUserToGroup($group, $user);
                if (
                    !empty($isUserAddedToGroup['errors']) &&
                    $isUserAddedToGroup['errors'] !== 'This user already has this group'
                ) {
                    throw new AddUserToAGroupInSignatoryBookFailedProblem($isUserAddedToGroup);
                }
            }
        }

        return $group;
    }
}
