<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Contact\Infrastructure\Service;

use Contact\controllers\ContactController;
use Entity\models\EntityModel;
use Exception;
use MaarchCourrier\Core\Domain\Contact\Port\AfnorContactServiceInterface;
use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use User\models\UserModel;

class AfnorContactService implements AfnorContactServiceInterface
{
    /**
     * @throws Exception
     */
    public function getAfnorByContact(ContactInterface $contact): array
    {
        return ContactController::getContactAfnor([
            'company'             => $contact->getCompany(),
            'firstname'           => $contact->getFirstname(),
            'lastname'            => $contact->getLastname(),
            'civility'            => $contact->getCivility(),
            'address_number'      => $contact->getAddressNumber(),
            'address_street'      => $contact->getAddressStreet(),
            'address_additional1' => $contact->getAddressAdditional1(),
            'address_additional2' => $contact->getAddressAdditional2(),
            'address_postcode'    => $contact->getAddressPostcode(),
            'address_town'        => $contact->getAddressTown(),
            'address_country'     => $contact->getAddressCountry()
        ]);
    }

    /**
     * @param CurrentUserInterface $currentUser
     * @return array
     * @throws Exception
     */
    public function getAfnorByCurrentUserPrimaryEntity(CurrentUserInterface $currentUser): array
    {
        $addressEntity = UserModel::getPrimaryEntityById([
            'id'     => $currentUser->getCurrentUser()->getId(),
            'select' => [
                'entities.entity_id',
                'entities.short_label',
                'entities.address_number',
                'entities.address_street',
                'entities.address_additional1',
                'entities.address_additional2',
                'entities.address_postcode',
                'entities.address_town',
                'entities.address_country'
            ]
        ]);
        $entityRoot = EntityModel::getEntityRootById(['entityId' => $addressEntity['entity_id']]);

        $addressEntity = ContactController::getContactAfnor([
            'company'             => $entityRoot['entity_label'],
            'civility'            => '',
            'firstname'           => $addressEntity['short_label'],
            'lastname'            => '',
            'address_number'      => $addressEntity['address_number'],
            'address_street'      => $addressEntity['address_street'],
            'address_additional1' => $addressEntity['address_additional1'],
            'address_additional2' => $addressEntity['address_additional2'],
            'address_postcode'    => $addressEntity['address_postcode'],
            'address_town'        => $addressEntity['address_town'],
            'address_country'     => $addressEntity['address_country']
        ]);

        return $addressEntity;
    }
}
