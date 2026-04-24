<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Add Substitute In Signatory Book
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\User;

use Exception;
use MaarchCourrier\Basket\Infrastructure\Repository\RedirectBasketRepository;
use MaarchCourrier\Core\Domain\Basket\Port\RedirectBasketRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookUserServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\AddSubstituteInSignatoryBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\DeleteSubstituteInSignatoryBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatorySubstituteAlreadyExistsProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserExternalIdNotFoundProblem;

class AddSubstituteInSignatoryBook
{
    /**
     * @param SignatureBookUserServiceInterface $signatureBookUserService
     * @param SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader
     * @param RedirectBasketRepository $redirectBasketRepository
     */
    public function __construct(
        private readonly SignatureBookUserServiceInterface $signatureBookUserService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader,
        private readonly RedirectBasketRepositoryInterface $redirectBasketRepository,
    ) {
    }

    /**
     * @param UserInterface $ownerUser
     * @param UserInterface $substitute
     * @param UserInterface|null $intermediateUser
     * @return void
     * @throws AddSubstituteInSignatoryBookProblem
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserExternalIdNotFoundProblem
     * @throws DeleteSubstituteInSignatoryBookProblem
     * @throws Exception
     */
    public function addSubstitute(
        UserInterface $ownerUser,
        UserInterface $substitute,
        ?UserInterface $intermediateUser
    ): void {
        $signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->signatureBookUserService->setConfig($signatureBook);

        if (!empty($ownerUser->getInternalParapheur()) && !empty($substitute->getInternalParapheur())) {
            $basketRedirectedForOwner = $this->redirectBasketRepository->getRedirectedBasketsByUser($ownerUser);
            $substitutionAlreadyExists = false;

            $tabSubstitutionUsers = [];
            foreach ($basketRedirectedForOwner as $redirectBasket) {
                $tabSubstitutionUsers[] = $redirectBasket['actual_user_id'];
            }

            $nbOccurrences = array_count_values($tabSubstitutionUsers);
            if (isset($nbOccurrences[$substitute->getId()])) {
                $substitutionAlreadyExists = true;
            }

            if (!empty($intermediateUser)) {
                if (
                    isset($nbOccurrences[$intermediateUser->getId()]) &&
                    $nbOccurrences[$intermediateUser->getId()] == 1
                ) {
                    $deleteSubstitute = new DeleteSubstituteInSignatoryBook(
                        $this->signatureBookUserService,
                        $this->signatureServiceConfigLoader
                    );

                    $deleteSubstitute->deleteSubstitute($ownerUser, $intermediateUser);
                }
            }

            if (!$substitutionAlreadyExists) {
                $isSubstituteAdded = $this->signatureBookUserService->addSubstitute($ownerUser, $substitute);
                if (!empty($isSubstituteAdded['errors'])) {
                    throw new AddSubstituteInSignatoryBookProblem($isSubstituteAdded);
                }
            }
        } else {
            throw new UserExternalIdNotFoundProblem();
        }
    }

    /**
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserExternalIdNotFoundProblem
     * @throws Exception
     */
    public function addSignatorySubstitute(
        UserInterface $ownerSignatory,
        UserInterface $signatorySubstitute
    ): void {
        $signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->signatureBookUserService->setConfig($signatureBook);

        if (!empty($ownerSignatory->getInternalParapheur()) && !empty($signatorySubstitute->getInternalParapheur())) {
            $signatorySubstitutedForUser = $ownerSignatory->getSignatureSubstitutes();
            $signatorySubstitutionAlreadyExists = false;

            $tabSubstitutionUsers = [];
            foreach ($signatorySubstitutedForUser as $signatorySubstituted) {
                $tabSubstitutionUsers[] = $signatorySubstituted;
            }

            $nbOccurrences = array_count_values($tabSubstitutionUsers);
            if (isset($nbOccurrences[$signatorySubstitute->getId()])) {
                $signatorySubstitutionAlreadyExists = true;
            }

            if (!$signatorySubstitutionAlreadyExists) {
                $isSignatorySubstituteAdded = $this->signatureBookUserService->addSubstitute(
                    $ownerSignatory,
                    $signatorySubstitute
                );
                if (!empty($isSignatorySubstituteAdded['errors'])) {
                    throw new AddSubstituteInSignatoryBookProblem($isSignatorySubstituteAdded);
                }
            } else {
                throw new SignatorySubstituteAlreadyExistsProblem();
            }
        } else {
            throw new UserExternalIdNotFoundProblem();
        }
    }
}
