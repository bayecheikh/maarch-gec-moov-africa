<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief  SignatureBookRepository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Repository;

use Exception;
use MaarchCourrier\Core\Domain\Basket\Port\GroupBasketRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookRepositoryInterface;
use SignatureBook\controllers\SignatureBookController;

class SignatureBookRepository implements SignatureBookRepositoryInterface
{
    public function __construct(
        private readonly GroupBasketRepositoryInterface $groupBasketRepository
    ) {
    }

    /**
     * @param MainResourceInterface $mainResource
     * @param UserInterface $user
     *
     * @return bool
     * @throws Exception
     */
    public function canAddAttachmentsInSignatureBook(MainResourceInterface $mainResource, UserInterface $user): bool
    {
        return SignatureBookController::isResourceInSignatureBook([
            'resId'             => $mainResource->getResId(),
            'userId'            => $user->getId(),
            'canAddAttachments' => true
        ]);
    }

    /**
     * @param MainResourceInterface $mainResource
     * @param UserInterface $user
     *
     * @return bool
     * @throws Exception
     */
    public function canUpdateResourcesInSignatureBook(MainResourceInterface $mainResource, UserInterface $user): bool
    {
        return SignatureBookController::isResourceInSignatureBook([
            'resId'              => $mainResource->getResId(),
            'userId'             => $user->getId(),
            'canUpdateDocuments' => true
        ]);
    }

    /**
     * @param MainResourceInterface $mainResource
     * @param UserInterface $user
     *
     * @return bool
     * @throws Exception
     */
    public function isMainResourceInSignatureBookBasket(MainResourceInterface $mainResource, UserInterface $user): bool
    {
        return SignatureBookController::isResourceInSignatureBook([
            'resId'  => $mainResource->getResId(),
            'userId' => $user->getId()
        ]);
    }

    /**
     * @param  int  $groupId
     * @param  int  $basketId
     * @return array
     * @throws Exception
     */
    public function retrieveSignatureBookPrivileges(int $groupId, int $basketId): array
    {
        $privileges = [
            'canAddDocumentInSignatureBook'          => false,
            'canUpdateRemoveDocumentInSignatureBook' => false
        ];
        $currentGroupBasket = $this->groupBasketRepository->getGroupBasket($groupId, $basketId);
        if (!empty($currentGroupBasket) && !empty($currentGroupBasket->getListEventData())) {
            $listEventData = $currentGroupBasket->getListEventData();
            if (!empty($listEventData['canUpdateDocuments'])) {
                $privileges['canUpdateRemoveDocumentInSignatureBook'] = true;
            }

            if (!empty($listEventData['canAddAttachments'])) {
                $privileges['canAddDocumentInSignatureBook'] = true;
            }
        }

        return $privileges;
    }
}
