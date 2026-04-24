<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Send To Maileva class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Application\Maileva;

use Exception;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Contact\Port\AfnorContactServiceInterface;
use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\Contact\Problem\PrimaryEntityAddressOfCurrentUserIsNotFilledEnoughProblem;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\MailevaTemplate;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\MailevaApiServiceInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\MailevaTemplateRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\ShippingRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaConfigNotFoundProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaEreSenderNotFoundInTemplateProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaIsDisabledProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaTemplateNotFoundProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Shipping;
use MaarchCourrier\ExternalExport\Domain\Port\ExternalFieldUpdaterServiceInterface;
use Psr\Log\LoggerInterface;

class SendToMaileva
{
    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly LoggerInterface $logger,
        private readonly MailevaConfiguration $mailevaConfiguration,
        private readonly MailevaTemplateRepositoryInterface $mailevaTemplateRepository,
        private readonly MailevaDocumentToSendPreparation $mailevaDocumentToSendPreparation,
        private readonly MailevaApiServiceInterface $mailevaApiService,
        private readonly AfnorContactServiceInterface $afnorContactService,
        private readonly ExternalFieldUpdaterServiceInterface $externalExportService,
        private readonly CurrentUserInterface $currentUser,
        private readonly ShippingRepositoryInterface $shippingRepository,
        private readonly MailevaShippingFeeCalculation $mailevaShippingFeeCalculation
    ) {
    }

    /**
     * Processes and sends documents via the Maileva API based on the given action, template, and data.
     * Handles the creation of sending records, document uploads, recipient assignments, and shipping options.
     *
     * @param int $actionId
     * @param int $shippingTemplateId
     * @param array $data
     *
     * @return array Returns an associative array with the following keys:
     *  - `warnings` (array<string>): An array of warning messages encountered during the process.
     *
     *  Workflow:
     *  - Retrieves the Maileva configuration and template by ID.
     *  - Prepares documents for sending, grouping them by contact's AFNOR-compliant address.
     *  - Authorizes the Maileva API and creates sending records for each document and recipient.
     *  - Uploads documents and assigns sending options (e.g., color printing, duplex printing).
     *  - Sends the finalized "sendings" to the Maileva service.
     *  - Logs warnings encountered during document preparation or while interacting with the Maileva API.
     *
     *  Process Details:
     *  - **Document Preparation**: Groups documents by recipient's AFNOR address.
     * Logs and skips invalid address configurations.
     *  - **Sending Creation**: Constructs sending configurations based on template and recipient data.
     *  - **Shipping Record**: Creates local database records for tracking shipping details,
     * user, recipient, account, and fees.
     *
     *  Problems:
     *  - May throw problems indirectly through sub-methods
     * (e.g., if Maileva API authentication or sending creation fails).
     *  - Catches problems (`Problem $p`) and appends their messages to the returned `warnings` array.
     *
     * @throws MailevaTemplateNotFoundProblem
     * @throws MailevaEreSenderNotFoundInTemplateProblem
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     * @throws PrimaryEntityAddressOfCurrentUserIsNotFilledEnoughProblem
     */
    public function send(int $actionId, int $shippingTemplateId, array $data): array
    {
        $return = [];

        // Step 1: Validate template and modes
        $template = $this->getTemplate($shippingTemplateId);
        [$isRegisteredMail, $isEre] = $this->determineSendMode($template);

        // Step 2: Prepare documents
        $prepared = $this->prepareDocuments($data, $isEre, $return);
        $documentsByContactByEnvelopeName = $prepared['documentByContact'];
        $recipientContactsFromPreparedDocuments = $prepared['contacts'];

        // Step 3: Configure API and authenticate
        $this->configureAndAuthenticate($template);

        // Step 4: Resolve sender address
        [$senderAddress, $isSenderAddressValid] = $this->resolveSenderAddress($isEre, $return);

        // Step 5: Process each envelope and its documents
        $this->processSendings(
            $documentsByContactByEnvelopeName,
            $recipientContactsFromPreparedDocuments,
            $template,
            $isRegisteredMail,
            $isEre,
            $senderAddress,
            $isSenderAddressValid,
            $actionId,
            $return
        );

        return $return;
    }

    /**
     * @throws MailevaTemplateNotFoundProblem
     */
    private function getTemplate(int $shippingTemplateId): MailevaTemplate
    {
        $template = $this->mailevaTemplateRepository->getById($shippingTemplateId);
        if ($template === null) {
            throw new MailevaTemplateNotFoundProblem();
        }
        return $template;
    }

    /**
     * @throws MailevaEreSenderNotFoundInTemplateProblem
     */
    private function determineSendMode(MailevaTemplate $template): array
    {
        $options = $template->getOptions();
        $sendMode = $options['sendMode'] ?? '';
        $isRegistered = str_contains($sendMode, 'digital_registered_mail');
        $isEre = false;

        if (!$isRegistered && str_contains($sendMode, 'ere')) {
            $isEre = true;
            if (empty($options['senderId'])) {
                throw new MailevaEreSenderNotFoundInTemplateProblem($template);
            }
        }

        return [$isRegistered, $isEre];
    }

    private function prepareDocuments(array $mainResourceIds, bool $isEre, array &$return): array
    {
        $prepared = $this->mailevaDocumentToSendPreparation->prepareDocuments($mainResourceIds, $isEre);
        if (!empty($prepared['errors'] ?? [])) {
            $return['warnings'] = $prepared['errors'];
        }

        return $prepared;
    }

    /**
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     */
    private function configureAndAuthenticate(MailevaTemplate $template): void
    {
        $config = $this->mailevaConfiguration->getMailevaConfiguration();
        $this->mailevaApiService->setConfig($config, $template);
        $this->mailevaApiService->getAuthToken();
    }

    /**
     * Depending on the sendMode, get if possible the sender primary entity address
     *
     * @param bool $isEre Is sendMode ERE
     * @param array $return
     * @return array [?array, bool] - The address and address state
     * @throws PrimaryEntityAddressOfCurrentUserIsNotFilledEnoughProblem
     */
    private function resolveSenderAddress(bool $isEre, array &$return): array
    {
        if ($isEre) {
            return [null, false];
        }

        $address = $this->afnorContactService->getAfnorByCurrentUserPrimaryEntity($this->currentUser);
        $valid = !(
            empty($address[1]) ||
            empty($address[2]) ||
            empty($address[6]) ||
            !preg_match('/^\d{5}\s/', $address[6])
        );

        if (!$valid) {
            $this->logWarning(
                $return,
                'The Sender primary entity address is not filled enough. It won’t be added to all sendings.'
            );
            return [null, false];
        }

        return [$address, true];
    }

    /**
     * The general sending process
     */
    private function processSendings(
        array $documentsByContact,
        array $recipientContactsFromPreparedDocuments,
        MailevaTemplate $template,
        bool $isRegisteredMail,
        bool $isEre,
        ?array $senderAddress,
        bool $isSenderAddressValid,
        int $actionId,
        array &$return
    ): void {
        foreach ($documentsByContact as $envelopeName => $recipientGroups) {
            foreach ($recipientGroups as $contactId => $documents) {
                $sendingId = $this->createSending(
                    $envelopeName,
                    $template,
                    $isRegisteredMail,
                    $isEre,
                    $senderAddress,
                    $isSenderAddressValid,
                    $return
                );
                if (!$sendingId) {
                    continue;
                }

                $resources = $this->uploadDocuments($sendingId, $documents, $return, $envelopeName);
                if (empty($resources)) {
                    $this->deleteSending($sendingId, $return);
                    continue;
                }

                $success = $this->setupRecipient(
                    $envelopeName,
                    $sendingId,
                    $recipientContactsFromPreparedDocuments[$contactId],
                    $return
                );
                if (!$success) {
                    $this->deleteSending($sendingId, $return);
                    continue;
                }

                $success = $this->setupOptions(
                    $envelopeName,
                    $sendingId,
                    $template,
                    $isRegisteredMail,
                    $isEre,
                    $return
                );
                if (!$success) {
                    $this->deleteSending($sendingId, $return);
                    continue;
                }

                //Send to Maileva
                try {
                    $this->mailevaApiService->send($sendingId);
                } catch (Exception $e) {
                    $this->logWarning($return, $e->getMessage(), $e->getTrace());
                    $this->deleteSending($sendingId, $return);
                    unset($return['sendDocs'][$envelopeName][$sendingId]);
                    continue;
                }
            }
        }

        $this->buildShippingRecords(
            $actionId,
            $template,
            $return
        );
    }

    private function createSending(
        string $envelopeName,
        MailevaTemplate $template,
        bool $isRegisteredMail,
        bool $isEre,
        ?array $senderAddress,
        bool $isSenderAddressValid,
        array &$return
    ): ?string {
        try {
            $body = ['name' => mb_strimwidth($envelopeName, 0, 255)];
            if ($isSenderAddressValid) {
                $body = array_merge($body, [
                    'sender_address_line_1' => $senderAddress[1] ?? null,
                    'sender_address_line_2' => $senderAddress[2] ?? null,
                    'sender_address_line_3' => $senderAddress[3] ?? null,
                    'sender_address_line_4' => $senderAddress[4] ?? null,
                    'sender_address_line_5' => $senderAddress[5] ?? null,
                    'sender_address_line_6' => $senderAddress[6] ?? null,
                    'sender_country_code'   => 'FR',
                ]);
            }

            if ($isRegisteredMail) {
                $body['acknowledgement_of_receipt'] =
                    ($template->getOptions()['sendMode'] ?? '') === 'digital_registered_mail_with_AR';
            } elseif ($isEre) {
                $body = array_merge($body, [
                    'sender_id'             => $template->getOptions()['senderId'],
                    'notification_language' => strtoupper($this->environment->getLanguageType()),
                ]);
            }

            return $this->mailevaApiService->createSending($body);
        } catch (Exception $e) {
            $this->logWarning($return, $e->getMessage(), $e->getTrace());
            return null;
        }
    }

    private function uploadDocuments(
        string $sendingId,
        array $documents,
        array &$return,
        string $envelopeName
    ): array {
        $resources = [];
        foreach ($documents as $doc) {
            try {
                $resource = $doc['resource'];
                $name = $resource instanceof MainResourceInterface
                    ? $resource->getSubject()
                    : $resource->getTitle();

                $this->mailevaApiService->setDocumentForSending(
                    $sendingId,
                    $name,
                    $doc['fileContent']
                );

                $resources[] = $resource;
                $return['sendDocs'][$envelopeName][$sendingId]['resources'][] = $resource;
            } catch (Exception $e) {
                $this->logWarning($return, $e->getMessage(), $e->getTrace());
            }
        }

        if (empty($resources)) {
            $this->logWarning(
                $return,
                _NO_DOCUMENTS_WERE_TRANSMITTED_TO_MAILEVA_,
                ['envelopeName' => $envelopeName]
            );
        }

        return $resources;
    }

    private function setupRecipient(
        string $envelopeName,
        string $sendingId,
        ContactInterface $recipientContact,
        array &$return
    ): bool {
        try {
            $recipientInfo = $this->mailevaApiService->setRecipientForSending($sendingId, $recipientContact);
            $return['sendDocs'][$envelopeName][$sendingId]['recipientOfSending'] = $recipientInfo;
            $return['sendDocs'][$envelopeName][$sendingId]['contactInfo'] = $recipientContact;

            return true;
        } catch (Exception $e) {
            $this->logWarning($return, $e->getMessage(), $e->getTrace());
            unset($return['sendDocs'][$envelopeName][$sendingId]);
            return false;
        }
    }

    private function setupOptions(
        string $envelopeName,
        string $sendingId,
        MailevaTemplate $template,
        bool $isRegisteredMail,
        bool $isEre,
        array &$return
    ): bool {
        try {
            // ere mode does not need options
            if (!$isEre) {
                $options = $template->getOptions()['shapingOptions'] ?? [];
                $body = [
                    'color_printing'         => in_array('color', $options),
                    'duplex_printing'        => in_array('duplexPrinting', $options),
                    'optional_address_sheet' => in_array('addressPage', $options),
                    'envelope_windows_type'  => in_array('envelopeWindowsType', $options) ? 'DOUBLE' : 'SIMPLE',
                ];

                if (!$isRegisteredMail) {
                    $body['postage_type'] = strtoupper($template->getOptions()['sendMode'] ?? 'FAST');
                }

                $this->mailevaApiService->setSendingOptions($sendingId, $body);
            }

            return true;
        } catch (Exception $e) {
            $this->logWarning($return, $e->getMessage(), $e->getTrace());
            unset($return['sendDocs'][$envelopeName][$sendingId]);
            return false;
        }
    }

    /**
     * @throws Exception
     */
    private function buildShippingRecords(
        int $actionId,
        MailevaTemplate $template,
        array &$return
    ): void {
        if (!empty($return['sendDocs'] ?? null)) {
            foreach ($return['sendDocs'] as $envelopeName => $sendingIds) {
                foreach ($sendingIds as $sendingId => $info) {
                    $sendDocs[$envelopeName][$sendingId] = $info['resources'] ?? [];

                    $fee = $this->mailevaShippingFeeCalculation->calculateTotalFee(
                        template: $template,
                        groupedMailings: $sendDocs
                    );
                    $sendDocs = [];

                    foreach ($info['resources'] as $resource) {
                        //Update the associative document with the sendingId
                        $this->externalExportService->updateExternalField(
                            'mailevaSendingId',
                            $sendingId,
                            $resource
                        );

                        //Create Shipping record in db
                        $theProcessingEntityOfMainDocument = match (true) {
                            $resource instanceof MainResourceInterface => $resource->getDestination(),
                            $resource instanceof AttachmentInterface => $resource->getDestinationFromMainResource(),
                            default => null
                        };

                        $shipping = (new Shipping())
                            ->setUser($this->currentUser->getCurrentUser())
                            ->setSendingId($sendingId)
                            ->setResource($resource)
                            ->setOptions($template->getOptions())
                            ->setFee($fee)
                            ->setRecipientEntity($theProcessingEntityOfMainDocument)
                            ->setAccountId($template->getAccount()['id'])
                            ->setRecipients(
                                [array_merge(['contactInfo' => $info['contactInfo']], $info['recipientOfSending'])]
                            )
                            ->setActionId($actionId)
                            ->setMailevaTemplate($template);
                        $this->shippingRepository->create($shipping);
                    }
                }
            }
            unset($return['sendDocs']);
        }
    }

    private function logWarning(array &$array, string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
        $array['warnings'][] = $message;
    }

    private function deleteSending(string $sendingId, array &$array): void
    {
        try {
            $this->mailevaApiService->deleteSending($sendingId);
        } catch (Exception $e) {
            $this->logWarning($array, $e->getMessage(), $e->getTrace());
        }
    }
}
