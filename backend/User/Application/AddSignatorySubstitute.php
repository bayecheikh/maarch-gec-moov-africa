<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Add Signatory Substitute
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Application;

use Exception;
use MaarchCourrier\Core\Domain\Basket\Port\RedirectBasketRepositoryInterface;
use MaarchCourrier\Core\Domain\Port\DatabaseServiceInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;
use MaarchCourrier\Core\Domain\SignatureBook\Port\AddSubstituteInSignatoryBookFactoryInterface;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\User\Domain\Problem\NotCurrentUserProblem;

class AddSignatorySubstitute
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RedirectBasketRepositoryInterface $redirectBasketRepository,
        private readonly AddSubstituteInSignatoryBookFactoryInterface $addSubstituteInSignatoryBookFactory,
        private readonly DatabaseServiceInterface $databaseService,
        private readonly CurrentUserInterface $currentUserInformations
    ) {
    }

    /**
     * @throws Exception
     */
    public function execute(
        int $ownerSignatoryId,
        int $signatorySubstituteId
    ): array {
        $ownerSignatory = $this->userRepository->getUserById($ownerSignatoryId);
        if (empty($ownerSignatory)) {
            throw new UserDoesNotExistProblem($ownerSignatory);
        }
        $signatorySubstitute = $this->userRepository->getUserById($signatorySubstituteId);
        if (empty($signatorySubstitute)) {
            throw new UserDoesNotExistProblem($signatorySubstitute);
        }

        if ($this->currentUserInformations->getCurrentUserId() !== $ownerSignatory->getId()) {
            throw new NotCurrentUserProblem();
        }

        $this->databaseService->beginTransaction();

        try {
            $this->userRepository->addSignatorySubstitute($ownerSignatory, $signatorySubstitute);

            $check = $this->redirectBasketRepository->getRedirectedBasketsByUser($ownerSignatory);
            if (!empty($check)) {
                $check = array_column($check, 'actual_user_id');
                if (!in_array($signatorySubstitute->getId(), $check)) {
                    $addSignatorySubstitute = $this->addSubstituteInSignatoryBookFactory->create();
                    $addSignatorySubstitute->addSignatorySubstitute($ownerSignatory, $signatorySubstitute);
                }
            } else {
                $addSignatorySubstitute = $this->addSubstituteInSignatoryBookFactory->create();
                $addSignatorySubstitute->addSignatorySubstitute($ownerSignatory, $signatorySubstitute);
            }
        } catch (Problem $p) {
            $this->databaseService->rollbackTransaction();
            throw $p;
        }

        $this->databaseService->commitTransaction();

        return [
            'addedSignatorySubstitute' => $signatorySubstitute->getId(),
        ];
    }
}
