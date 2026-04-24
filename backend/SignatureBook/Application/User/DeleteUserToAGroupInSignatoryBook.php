<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Delete User To A Group In Signatory Book
 */

namespace MaarchCourrier\SignatureBook\Application\User;

use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookUserServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\DeleteUserToAGroupInSignatoryBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\GroupExternalIdNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserExternalIdNotFoundProblem;

class DeleteUserToAGroupInSignatoryBook
{
    public function __construct(
        private readonly SignatureBookUserServiceInterface $signatureBookUserService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader,
    ) {
    }

    /**
     * @throws SignatureBookNoConfigFoundProblem
     * @throws GroupExternalIdNotFoundProblem
     * @throws DeleteUserToAGroupInSignatoryBookFailedProblem
     * @throws UserExternalIdNotFoundProblem
     */
    public function deleteUserToAGroup(UserInterface $user, GroupInterface $group): UserInterface
    {
        $signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->signatureBookUserService->setConfig($signatureBook);

        $groupExternalId = $group->getExternalId();
        $userExternalId = $user->getExternalId();
        if (!empty($groupExternalId['internalParapheur'])) {
            if (!empty($userExternalId['internalParapheur'])) {
                $isUserDeletedToGroup = $this->signatureBookUserService->deleteUserToGroup($user, $group);
                if (!empty($isUserDeletedToGroup['errors'])) {
                    throw new DeleteUserToAGroupInSignatoryBookFailedProblem($isUserDeletedToGroup);
                }
            } else {
                throw new UserExternalIdNotFoundProblem();
            }
        } else {
            throw new GroupExternalIdNotFoundProblem();
        }
        return $user;
    }
}
