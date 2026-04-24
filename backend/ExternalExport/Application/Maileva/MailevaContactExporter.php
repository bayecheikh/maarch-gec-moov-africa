<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Afnor Address Builder class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Application\Maileva;

use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\Contact\Port\AfnorContactServiceInterface;
use MaarchCourrier\Core\Domain\Contact\Problem\ContactEmailAddressIsInvalidProblem;
use MaarchCourrier\Core\Domain\Contact\Problem\ContactEmailAddressIsRequiredProblem;
use MaarchCourrier\Core\Domain\Contact\Problem\ContactPostalAddressIsNotCompleteEnoughProblem;
use MaarchCourrier\Core\Domain\Contact\Problem\ContactPostalAddressIsNotInFranceProblem;
use Psr\Log\LoggerInterface;

class MailevaContactExporter
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AfnorContactServiceInterface $afnorContactService
    ) {
    }

    /**
     * Formats a contact's AFNOR-compliant address as a string or returns an error array.
     *
     * @param ContactInterface $contact The contact to be formatted into an AFNOR address.
     *
     * @return string A formatted AFNOR address as a string when the contact's information meets the requirements.
     * AFNOR address values are concatenated using a "|" delimiter and returned as a string.
     *
     * @throws ContactPostalAddressIsNotInFranceProblem
     * @throws ContactPostalAddressIsNotCompleteEnoughProblem
     *
     * Logging:
     *  - Warnings are logged when the method determines that the contact's address is invalid for sending.
     */
    public function buildAfnorAddress(ContactInterface $contact): string
    {
        if (
            empty($contact->getAddressCountry()) ||
            strtoupper(trim($contact->getAddressCountry())) !== 'FRANCE'
        ) {
            $contactId = $contact->getId();
            $contactFirstName = $contact->getFirstname();
            $contactLastName = $contact->getLastname();
            $this->logger->warning(
                "Contact country of '$contactId $contactFirstName $contactLastName' is not in France."
            );
            throw new ContactPostalAddressIsNotInFranceProblem($contact);
        }

        $afnorAddress = $this->afnorContactService->getAfnorByContact($contact);

        if (
            (empty($afnorAddress[1]) && empty($afnorAddress[2])) || empty($afnorAddress[6]) ||
            !preg_match("/^\d{5}\s/", $afnorAddress[6])
        ) {
            $contactFullName = "{$contact->getFirstname()} {$contact->getLastname()}";
            $info = "Contact address of '$contactFullName' is not complete enough.";
            $this->logger->warning($info);
            throw new ContactPostalAddressIsNotCompleteEnoughProblem($contact);
        }

        return implode('|', $afnorAddress);
    }

    /**
     * Get the contact email as a string.
     * @param ContactInterface $contact The contact to get the email.
     *
     * @return string The email as a string when the contact's information meets the requirements.
     *
     * @throws ContactEmailAddressIsRequiredProblem
     * @throws ContactEmailAddressIsInvalidProblem
     *
     * Logging:
     *  - Warnings are logged when the method determines that the contact's email is invalid for sending.
     *
     * @see MailevaContactExporter For full behavior logic
     *
     */
    public function getEmailAddress(ContactInterface $contact): string
    {
        if (empty($contact->getEmail())) {
            $info = "Email address is required.";
            $this->logger->warning($info);
            throw new ContactEmailAddressIsRequiredProblem($contact);
        }

        if (filter_var($contact->getEmail(), FILTER_VALIDATE_EMAIL) === false) {
            $info = "Email address is invalid.";
            $this->logger->warning($info);
            throw new ContactEmailAddressIsInvalidProblem($contact);
        }

        return $contact->getEmail();
    }
}
