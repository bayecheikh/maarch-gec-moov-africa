<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Book Action Permission Checker
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Action\Checker;

use MaarchCourrier\Core\Domain\Basket\Port\BasketClauseServiceInterface;
use MaarchCourrier\Core\Domain\Basket\Port\BasketRepositoryInterface;
use MaarchCourrier\Core\Domain\Basket\Port\RedirectBasketRepositoryInterface;
use MaarchCourrier\Core\Domain\Basket\Problem\BasketNotFoundProblem;
use MaarchCourrier\Core\Domain\Group\Port\GroupRepositoryInterface;
use MaarchCourrier\Core\Domain\Group\Problem\GroupDoesNotExistProblem;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;

class SignatureBookActionPermissionChecker
{
    public function __construct(
        private readonly BasketRepositoryInterface $basketRepository,
        private readonly BasketClauseServiceInterface $basketClauseService,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly VisaWorkflowRepositoryInterface $visaWorkflowRepository,
        private readonly RedirectBasketRepositoryInterface $redirectBasketRepository
    ) {
    }

    /**
     * The function validates if the connected user can perform an action in his signature book basket.
     *  Can perform an action if :
     *   - A user redirected his basket to the connected user
     *   - The connected user is the current workflow user step of the main resource
     *   - The main resource is present in a monitoring basket and the connected user is the current workflow user step
     *
     * @param MainResourceInterface $mainResource
     * @param UserInterface $connectedUser
     * @param UserInterface $basketOwner
     * @param int $groupId
     * @param int $basketId Assuming it's a signature book basket
     *
     * @return bool
     * @throws GroupDoesNotExistProblem
     * @throws BasketNotFoundProblem
     */
    public function check(
        MainResourceInterface $mainResource,
        UserInterface $connectedUser,
        UserInterface $basketOwner,
        int $groupId,
        int $basketId
    ): bool {
        $group = $this->groupRepository->getById($groupId);
        if ($group === null) {
            throw new GroupDoesNotExistProblem();
        }

        $basket = $this->basketRepository->getBasketById($basketId);

        if ($basket === null) {
            throw new BasketNotFoundProblem();
        }

        // check if the basket is redirected to connected user
        $isBasketAssignedToConnectedUser = $this->redirectBasketRepository->isBasketAssignedToUserOfGroup(
            $basketOwner,
            $connectedUser,
            $group,
            $basket
        );
        if ($isBasketAssignedToConnectedUser) {
            return true;
        }

        $basketClause = '';
        if (!empty($basket->getClause())) {
            $basketClause = $this->basketClauseService->prepare($basket, $connectedUser);
        }

        // verify if main resource is in the basket
        $isMainResourceFoundInSignatureBookBasket = $this->mainResourceRepository->doesExistOnView(
            $mainResource,
            $basketClause
        );

        $currentWorkflowUserStep = $this->visaWorkflowRepository->getCurrentStepUserByMainResource($mainResource);

        // check if the connected user is the current workflow user step of main resource
        if (
            !$isMainResourceFoundInSignatureBookBasket ||
            empty($currentWorkflowUserStep) ||
            (
                $currentWorkflowUserStep->getId() != $connectedUser->getId() &&
                !in_array($connectedUser->getId(), $currentWorkflowUserStep->getSignatureSubstitutes())
            )
        ) {
            return false;
        }

        return true;
    }
}
