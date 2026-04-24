<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Delete Signatory Substitute
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Application;

use MaarchCourrier\Core\Domain\Basket\Port\RedirectBasketRepositoryInterface;
use MaarchCourrier\Core\Domain\Port\DatabaseServiceInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;
use MaarchCourrier\Core\Domain\SignatureBook\Port\DeleteSubstituteInSignatoryBookFactoryInterface;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\DeleteSubstituteInSignatoryBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserExternalIdNotFoundProblem;
use MaarchCourrier\User\Domain\Problem\NotCurrentUserProblem;
use MaarchCourrier\User\Domain\Problem\NotSignatorySubstituteProblem;

class DeleteSignatorySubstitute
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly CurrentUserInterface $currentUserInformations,
        private readonly RedirectBasketRepositoryInterface $redirectBasketRepository,
        private readonly DeleteSubstituteInSignatoryBookFactoryInterface $deleteSubstituteInSignatoryBookFactory,
        private readonly DatabaseServiceInterface $databaseService
    ) {
    }

    /**
     * @throws DeleteSubstituteInSignatoryBookProblem
     * @throws UserExternalIdNotFoundProblem
     * @throws UserDoesNotExistProblem
     * @throws SignatureBookNoConfigFoundProblem
     * @throws NotSignatorySubstituteProblem
     * @throws NotCurrentUserProblem
     * @throws Problem
     */
    public function execute(
        int $ownerSignatoryId,
        int $signatorySubstituteId,
        bool $skipCheckCurrentUser = false
    ): void {
        $ownerSignatory = $this->userRepository->getUserById($ownerSignatoryId);
        if (empty($ownerSignatory)) {
            throw new UserDoesNotExistProblem($ownerSignatoryId);
        }

        $signatorySubstitute = $this->userRepository->getUserById($signatorySubstituteId);
        if (empty($signatorySubstitute)) {
            throw new UserDoesNotExistProblem($signatorySubstituteId);
        }

        if ($this->currentUserInformations->getCurrentUserId() !== $ownerSignatory->getId() && !$skipCheckCurrentUser) {
            throw new NotCurrentUserProblem();
        }

        if (!in_array($signatorySubstitute->getId(), $ownerSignatory->getSignatureSubstitutes())) {
            throw new NotSignatorySubstituteProblem(
                ['ownerSignatory' => $ownerSignatoryId, 'signatorySubstitute' => $signatorySubstituteId]
            );
        }

        $this->databaseService->beginTransaction();
        $this->userRepository->removeSignatorySubstitute($ownerSignatory, $signatorySubstitute);

        $redirectedBaskets = $this->redirectBasketRepository->getRedirectedBasketsByUser($ownerSignatory);

        $delegatedUserInBaskets = false;
        foreach ($redirectedBaskets as $redirectedBasket) {
            if ($redirectedBasket['actual_user_id'] === $signatorySubstitute->getId()) {
                $delegatedUserInBaskets = true;
                break;
            }
        }

        if (!$delegatedUserInBaskets) {
            $deleteSubstituteInSignatoryBook = $this->deleteSubstituteInSignatoryBookFactory->create();
            try {
                $deleteSubstituteInSignatoryBook->deleteSubstitute($ownerSignatory, $signatorySubstitute);
            } catch (Problem $p) {
                $this->databaseService->rollbackTransaction();
                throw $p;
            }
        }
        $this->databaseService->commitTransaction();
    }
}
