<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Postal Address Is Not In France Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Contact\Problem;

use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;

class ContactPostalAddressIsNotInFranceProblem extends Problem
{
    public function __construct(ContactInterface $contact)
    {
        parent::__construct(
            _THE_CONTACT_POSTAL_ADDRESS . " " . _OF .
            " '{$contact->getId()} {$contact->getFirstname()} {$contact->getLastname()}' " .
            _IS_NOT_IN_FRANCE,
            404,
            [
                'contactId' => $contact->getId(),
                'contactFirstname' => $contact->getFirstname(),
                'contactLastname' => $contact->getLastname(),
            ],
            'contactPostalAddressIsNotInFrance'
        );
    }
}
