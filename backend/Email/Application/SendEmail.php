<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Send Email class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Application;

use MaarchCourrier\Core\Domain\Attachment\AttachmentNotFoundProblem;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentFileRetrieverFactoryInterface;
use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationInterface;
use MaarchCourrier\Core\Domain\Entity\Port\EntityRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\RetrieveMainResourceFileFactoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\RetrieveOriginalMainResourceFileFactoryInterface;
use MaarchCourrier\Core\Domain\MessageExchange\Port\MessageExchangeFileServiceInterface;
use MaarchCourrier\Core\Domain\Note\Port\GenerateEncodedPdfFactoryInterface;
use MaarchCourrier\Core\Domain\Note\Problem\CouldNotFindNotesProblem;
use MaarchCourrier\Core\Domain\Problem\Configuration\EmailServerConfigurationNotFoundProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterArrayCanNotBeEmptyProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterMustBeGreaterThanZeroException;
use MaarchCourrier\Core\Domain\User\UserMode;
use MaarchCourrier\DocumentStorage\Domain\Problem\DocServerDoesNotExistProblem;
use MaarchCourrier\DocumentStorage\Domain\Problem\DocumentFingerprintDoesNotMatchInDocServerProblem;
use MaarchCourrier\DocumentStorage\Domain\Problem\FileNotFoundInDocServerProblem;
use MaarchCourrier\Email\Domain\Port\EmailInterface;
use MaarchCourrier\Email\Domain\Port\EmailServiceAdapterInterface;
use Resource\Domain\Exceptions\ConvertedResultException;
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceFailedToGetDocumentFromDocserverException;
use Resource\Domain\Exceptions\ResourceFingerPrintDoesNotMatchException;
use Resource\Domain\Exceptions\ResourceHasNoFileException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;

class SendEmail
{
    private ?ConfigurationInterface $emailServerConfig = null;

    public function __construct(
        private readonly EmailServiceAdapterInterface $emailService,
        private readonly EntityRepositoryInterface $entityRepository,
        private readonly MessageExchangeFileServiceInterface $exchangeFileService,
        private readonly RetrieveOriginalMainResourceFileFactoryInterface $retrieveOriginalMainResourceFileFactory,
        private readonly RetrieveMainResourceFileFactoryInterface $retrieveMainResourceFileFactory,
        private readonly AttachmentFileRetrieverFactoryInterface $attachmentFileRetrieverFactory,
        private readonly GenerateEncodedPdfFactoryInterface $generateEncodedPdfFactory
    ) {
    }

    /**
     * The purpose of this code is to eliminate redundant email addresses
     * from lower-priority email fields (e.g., 'cci' or 'cc') if those email
     * addresses are already present in higher-priority fields ('recipients' or 'cc').
     *
     * Hierarchy logic:
     * - 'cci' must not contain any email address that is already present in 'recipients' or 'cc'.
     * - 'cc' must not contain any email address that is already present in 'recipients'.
     *
     * Steps:
     * 1. Iterate over each low-priority field ('cci', 'cc').
     * 2. For each low-priority field, check against its higher-priority fields.
     * 3. If an email address in the lower-priority field exists in a higher-priority
     *    field, remove it from the lower-priority field (using unset).
     *
     * This ensures that each email address appears only in the most relevant and
     * highest-priority field, avoiding redundancy when sending the email.
     */
    private function filterEmailHierarchy(EmailInterface $email): EmailInterface
    {
        // Define the hierarchy of email fields
        // 'cci' must not include addresses from 'recipients' and 'cc'
        // 'cc' must not include addresses from 'recipients'
        $hierarchyMail = [
            'cci' => ['recipients', 'cc'],
            'cc'  => ['recipients']
        ];

        // Iterate over each low-priority email field
        foreach ($hierarchyMail as $lowEmail => $highEmails) {
            // Convert the getters and setters dynamically
            $getLowEmail = 'get' . ucfirst($lowEmail); // e.g., 'getCci'
            $setLowEmail = 'set' . ucfirst($lowEmail); // e.g., 'setCci'

            // Get the email addresses from the low-priority field
            $lowEmailList = $email->$getLowEmail();

            // Loop through each higher-priority field
            foreach ($highEmails as $highEmail) {
                $getHighEmail = 'get' . ucfirst($highEmail); // e.g., 'getRecipients'

                // Get the email addresses from the higher-priority field
                $highEmailList = $email->$getHighEmail();

                // Remove duplicates: unset elements from the low-priority array
                foreach ($lowEmailList as $key => $lowEmailAddress) {
                    if (in_array($lowEmailAddress, $highEmailList)) {
                        unset($lowEmailList[$key]);
                    }
                }
            }

            // Set the cleaned-up low-priority email list back to the email object
            $email->$setLowEmail(array_values($lowEmailList)); // Re-index the array
        }

        return $email;
    }

    /**
     * @throws EmailServerConfigurationNotFoundProblem
     */
    private function setSenderEmail(EmailInterface $email): void
    {
        if ($this->emailServerConfig == null) {
            throw new EmailServerConfigurationNotFoundProblem();
        }

        $emailFrom = !empty($email->getSender()['email']) ?
            $email->getSender()['email'] : $this->emailServerConfig->getValue()['from'];

        if (empty($email->getSender()['entityId'] ?? null)) {
            $emailFromName = !in_array($email->getUser()->getMode(), [UserMode::VISIBLE_ROOT, UserMode::INVISIBLE_ROOT])
                ? $email->getUser()->getFullName()
                : null;
        } else {
            $entity = $this->entityRepository->getEntityById($email->getSender()['entityId']);
            $emailFromName = !empty($entity) ? $entity->getShortLabel() : null;
        }

        $this->emailService->setSender($emailFrom, $emailFromName);
    }

    /**
     * @param EmailInterface $email
     *
     * @return array An array of attachments where each item contains:
     *         - 'path': Encoded file content.
     *         - 'name': File name.
     * @throws CouldNotFindNotesProblem
     * @throws ParameterArrayCanNotBeEmptyProblem
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws DocServerDoesNotExistProblem
     * @throws DocumentFingerprintDoesNotMatchInDocServerProblem
     * @throws FileNotFoundInDocServerProblem
     * @throws ConvertedResultException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     * @throws AttachmentNotFoundProblem
     */
    private function prepareAttachments(EmailInterface $email): array
    {
        $attachments = [];
        if (!empty($email->getMessageExchangeId())) {
            $file = $this->exchangeFileService->getFileNameAndContentById($email->getMessageExchangeId());
            $attachments[] = ['fileContent' => $file['fileContent'], 'filename' => $file['filename']];
        } elseif (!empty($email->getDocuments())) {
            if (!empty($email->getDocuments()['isLinked'] ?? null)) {
                if (!empty($email->getDocuments()['original'] ?? false)) {
                    $factory = $this->retrieveOriginalMainResourceFileFactory::create();
                    $mainResourceFileInfo = $factory->getResourceFile($email->getDocuments()['id']);
                } else {
                    $factory = $this->retrieveMainResourceFileFactory::create();
                    $mainResourceFileInfo = $factory->getResourceFile($email->getDocuments()['id'], false);
                }
                $pathInfo = $mainResourceFileInfo->getPathInfo();
                $extension = $pathInfo['extension'];
                $name = "{$mainResourceFileInfo->getFormatFilename()}.{$extension}";

                $attachments[] = [
                    'fileContent' => $mainResourceFileInfo->getFileContent(),
                    'filename'    => $name
                ];
            }
            if (!empty($email->getDocuments()['attachments'] ?? null)) {
                foreach ($email->getDocuments()['attachments'] as $attachment) {
                    if (isset($attachment['id']) && isset($attachment['original'])) {
                        $factory = $this->attachmentFileRetrieverFactory::create();
                        $attachmentFileInfo = $factory->getById($attachment['id'], $attachment['original']);
                        $attachments[] = $attachmentFileInfo;
                    }
                }
            }
            if (!empty($email->getDocuments()['notes'] ?? null)) {
                $factory = $this->generateEncodedPdfFactory::create();
                $pdfFileContent = $factory->getByIds($email->getDocuments()['notes']);
                $attachments[] = ['fileContent' => $pdfFileContent, 'filename' => 'notes.pdf'];
            }
            if (!empty($email->getDocuments()['filePaths'] ?? null)) {
                foreach ($email->getDocuments()['filePaths'] as $path) {
                    $attachments[] = ['path' => $path];
                }
            }
        }
        return $attachments;
    }

    public function setEmailServerConfig(ConfigurationInterface $config): self
    {
        $this->emailServerConfig = $config;
        return $this;
    }

    /**
     * @param EmailInterface $email
     *
     * @return bool
     * @throws ConvertedResultException
     * @throws CouldNotFindNotesProblem
     * @throws DocServerDoesNotExistProblem
     * @throws DocumentFingerprintDoesNotMatchInDocServerProblem
     * @throws EmailServerConfigurationNotFoundProblem
     * @throws FileNotFoundInDocServerProblem
     * @throws ParameterArrayCanNotBeEmptyProblem
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     * @throws AttachmentNotFoundProblem
     */
    public function execute(EmailInterface $email): bool
    {
        if ($this->emailServerConfig == null) {
            throw new EmailServerConfigurationNotFoundProblem();
        }
        $email = $this->filterEmailHierarchy($email);

        $this->emailService->initialize($this->emailServerConfig, $email->getUser());
        $this->setSenderEmail($email);
        $this->emailService->setReplyToEmail($email->getSender()['replyTo'] ?? '');
        $recipients = $email->getRecipients();
        $cc = $email->getCc();
        $cci = $email->getCci();

        $emailRecipients = $emailsCc = $emailsCci = [];
        foreach ($recipients as $recipient) {
            if (!empty($recipient['email'])) {
                $emailRecipients[] = $recipient['email'];
            } elseif (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $emailRecipients[] = $recipient;
            }
        }

        foreach ($cc as $ccRecipient) {
            if (!empty($ccRecipient['email'])) {
                $emailsCc[] = $ccRecipient['email'];
            } elseif (filter_var($ccRecipient, FILTER_VALIDATE_EMAIL)) {
                $emailsCc[] = $ccRecipient;
            }
        }

        foreach ($cci as $cciRecipient) {
            if (!empty($cciRecipient['email'])) {
                $emailsCci[] = $cciRecipient['email'];
            } elseif (filter_var($cciRecipient, FILTER_VALIDATE_EMAIL)) {
                $emailsCci[] = $cciRecipient;
            }
        }

        $this->emailService->setRecipients(
            $emailRecipients,
            $emailsCc,
            $emailsCci
        );
        $this->emailService->setSubject($email->getObject());
        $this->emailService->setBody($email->getBody(), $email->isHtml());
        $this->emailService->addAttachments($this->prepareAttachments($email));
        return $this->emailService->send();
    }
}
