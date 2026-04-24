<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Email Address Is Invalid Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Contact\Problem;

use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;

class ContactEmailAddressIsInvalidProblem extends Problem
{
    public function __construct(ContactInterface $contact)
    {
        parent::__construct(
            _THE_CONTACT_EMAIL . " " . _OF .
            " '{$contact->getFirstname()} {$contact->getLastname()}' " .
            _IS_INVALID,
            404,
            [
                'contactId' => $contact->getId(),
                'contactFirstname' => $contact->getFirstname(),
                'contactLastname' => $contact->getLastname(),
            ],
            'theContactEmailAddressIsInvalid'
        );
    }
}
