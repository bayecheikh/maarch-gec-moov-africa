<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Document To Send Preparation class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Application\Maileva;

use Exception;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentFileRetrieverFactoryInterface;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\ResourceContactInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\ResourceContactsRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\RetrieveMainResourceFileFactoryInterface;
use MaarchCourrier\Core\Domain\MainResource\ResourceContactType;
use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;
use MaarchCourrier\DocumentStorage\Domain\Port\FileSystemServiceInterface;
use Psr\Log\LoggerInterface;

class MailevaDocumentToSendPreparation
{
    private bool $isEre = false;

    public function __construct(
        public readonly EnvironmentInterface $environment,
        public readonly LoggerInterface $logger,
        public readonly MainResourceRepositoryInterface $mainResourceRepository,
        public readonly AttachmentRepositoryInterface $attachmentRepository,
        public readonly ResourceContactsRepositoryInterface $resourceContactsRepository,
        public readonly MailevaContactExporter $mailevaContactExporter,
        public readonly RetrieveMainResourceFileFactoryInterface $retrieveMainResourceFileFactory,
        public readonly AttachmentFileRetrieverFactoryInterface $attachmentFileRetrieverFactory,
        public readonly FileSystemServiceInterface $fileSystemService
    ) {
    }

    /**
     * Prepares the documents and records errors associated with each main resource to be sent.
     *
     * @param int[] $mainResourceIds List of main resource IDs to process.
     * @param bool $isEre
     *
     * @return array Returns an array with the following structure:
     *  - `documentByContactAfnor`:
     *      Key: AFNOR address (string),
     *      Value: List of elements containing:
     *          - `resource`: The main resource or attachment object.
     *          - `fileContent`: The file content associated with the resource.
     *  - `errors`: List of errors encountered, represented as strings.
     */
    public function prepareDocuments(array $mainResourceIds, bool $isEre = false): array
    {
        $errors = [];
        $documents = [];
        $contacts = [];
        $this->isEre = $isEre;

        $mainResources = $this->mainResourceRepository->getMainResourcesByResIds($mainResourceIds);

        foreach ($mainResources as $mainResource) {
            $envelopeName = $this->generateEnvelopeName($mainResource);

            if ($mainResource->isInMailevaShipping()) {
                $this->processMainResource($mainResource, $envelopeName, $documents, $contacts, $errors);
            }

            $attachments = $this->attachmentRepository->getAttachmentsForMaileva($mainResource);

            foreach ($attachments as $attachment) {
                $this->processAttachment($attachment, $envelopeName, $documents, $contacts, $errors);
            }
        }

        return ['documentByContact' => $documents, 'contacts' => $contacts, 'errors' => $errors];
    }

    /**
     * Check whether a contact is eligible for sending based on the given resource.
     *
     * If ERE mode is disabled, this will validate the contact’s AFNOR address;
     *  otherwise it will validate their email address.
     *  On failure, a warning is logged and an error array is returned.
     *
     * @param ContactInterface $contact The contact to validate.
     * @param MainResourceInterface|AttachmentInterface $resource The main resource or attachment
     *   associated with the contact.
     *
     * @return ContactInterface|array Returns:
     *  - The original ContactInterface on success.
     *  - An array with an `error` key (containing the reason as a string) if the contact does not meet criteria.
     *
     * @see MailevaContactExporter::buildAfnorAddress()  For the AFNOR address validation logic.
     * @see MailevaContactExporter::getEmailAddress()    For the email validation logic.
     */
    private function checkRecipient(
        ContactInterface $contact,
        MainResourceInterface|AttachmentInterface $resource
    ): ContactInterface|array {
        try {
            if (!$this->isEre) {
                $this->mailevaContactExporter->buildAfnorAddress($contact);
            } else {
                $this->mailevaContactExporter->getEmailAddress($contact);
            }
        } catch (Exception $e) {
            $resourceType = $resource instanceof MainResourceInterface ? 'MainResource' : 'Attachment';
            $chrono = !empty($resource->getChrono()) ? $resource->getChrono() : '';
            return ['error' => "$resourceType $chrono won't be send : {$e->getMessage()}. Skip."];
        }

        return $contact;
    }

    private function processMainResource(
        MainResourceInterface $mainResource,
        string $envelopeName,
        array &$documents,
        array &$contacts,
        array &$errors
    ): void {
        $resourceContacts = $this->resourceContactsRepository->getRecipientContactsFromMainResource($mainResource);
        if (empty($resourceContacts)) {
            $this->logError(
                $errors,
                "MainResource '{$mainResource->getChrono()}' won't be sent. No recipients found."
            );
            return;
        }

        try {
            $resourceFileInfo = $this->retrieveMainResourceFileFactory::create()->getResourceFile(
                $mainResource->getResId(),
                false
            );
        } catch (Exception $p) {
            $info = "MainResource '{$mainResource->getChrono()}' : {$p->getMessage()}";
            $info = "$info. Won't be send, skip.";
            $this->logError($errors, $info);
            return;
        }

        foreach ($resourceContacts as $resourceContact) {
            if ($resourceContact->getType() === ResourceContactType::CONTACT) {
                $this->processContactForMainResourceDocument(
                    $resourceContact,
                    $mainResource,
                    $envelopeName,
                    $resourceFileInfo->getFileContent(),
                    $documents,
                    $contacts,
                    $errors
                );
            }
        }
    }

    private function processAttachment(
        AttachmentInterface $attachment,
        string $envelopeName,
        array &$documents,
        array &$contacts,
        array &$errors
    ): void {
        if (empty($attachment->getRecipient()) || $attachment->getRecipientType() !== 'contact') {
            $this->logError($errors, "Attachment '{$attachment->getChrono()}' won't be sent. Invalid recipient.");
            return;
        }
        $recipient = $this->checkRecipient($attachment->getRecipient(), $attachment);
        if (is_array($recipient) && !empty($recipient['error'])) {
            $errors[] = $recipient['error'];
            return;
        }

        try {
            $fileInfo = $this->attachmentFileRetrieverFactory::create()->getById($attachment->getResId(), false);
            $fileContent = $this->fileSystemService->getFileContent($fileInfo['path']);
        } catch (Problem $p) {
            $this->logError($errors, "Attachment '{$attachment->getChrono()}' won't be sent. {$p->getMessage()}.");
            return;
        }

        $contacts[$recipient->getId()] = $recipient;

        $documents[$envelopeName][$recipient->getId()][] = [
            'resource'    => $attachment,
            'fileContent' => $fileContent
        ];
    }

    private function processContactForMainResourceDocument(
        ResourceContactInterface $resourceContact,
        MainResourceInterface $mainResource,
        string $envelopeName,
        string $fileContent,
        array &$documents,
        array &$contacts,
        array &$errors
    ): void {
        $recipient = $this->checkRecipient($resourceContact->getItem(), $mainResource);
        if (is_array($recipient) && !empty($recipient['error'])) {
            $errors[] = $recipient['error'];
            return;
        }

        $contacts[$recipient->getId()] = $recipient;

        $documents[$envelopeName][$recipient->getId()][] = [
            'resource'    => $mainResource,
            'fileContent' => $fileContent
        ];
    }

    private function generateEnvelopeName(MainResourceInterface $mainResource): string
    {
        $chrono = $mainResource->getChrono();
        $subject = $mainResource->getSubject();
        return $chrono || $subject
            ? trim(sprintf('%s - %s', $chrono ?? '', $subject ?? ''), ' -')
            : $this->environment->getUniqueId();
    }

    private function logError(array &$errors, string $message): void
    {
        $this->logger->warning($message);
        $errors[] = $message;
    }
}
