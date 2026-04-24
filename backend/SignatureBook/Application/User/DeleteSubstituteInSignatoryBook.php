<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Delete Substitute In Signatory Book
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\User;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookUserServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\DeleteSubstituteInSignatoryBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserExternalIdNotFoundProblem;

class DeleteSubstituteInSignatoryBook
{
    /**
     * @param SignatureBookUserServiceInterface $signatureBookUserService
     * @param SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader
     */
    public function __construct(
        private readonly SignatureBookUserServiceInterface $signatureBookUserService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader
    ) {
    }

    /**
     * @param UserInterface $ownerUser
     * @param UserInterface $substitute
     * @return void
     * @throws DeleteSubstituteInSignatoryBookProblem
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserExternalIdNotFoundProblem
     */
    public function deleteSubstitute(UserInterface $ownerUser, UserInterface $substitute): void
    {
        $signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->signatureBookUserService->setConfig($signatureBook);

        if (!empty($ownerUser->getInternalParapheur()) && !empty($substitute->getInternalParapheur())) {
            $isSubstituteDeleted = $this->signatureBookUserService->deleteSubstitute(
                $ownerUser,
                $substitute
            );
            if (!empty($isSubstituteDeleted['errors'])) {
                throw new DeleteSubstituteInSignatoryBookProblem($isSubstituteDeleted);
            }
        } else {
            throw new UserExternalIdNotFoundProblem();
        }
    }
}
