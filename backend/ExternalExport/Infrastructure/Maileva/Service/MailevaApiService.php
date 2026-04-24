<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Api Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Infrastructure\Maileva\Service;

use Exception;
use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\Contact\Problem\ContactEmailAddressIsInvalidProblem;
use MaarchCourrier\Core\Domain\Contact\Problem\ContactEmailAddressIsRequiredProblem;
use MaarchCourrier\Core\Domain\Contact\Problem\ContactPostalAddressIsNotCompleteEnoughProblem;
use MaarchCourrier\Core\Domain\Contact\Problem\ContactPostalAddressIsNotInFranceProblem;
use MaarchCourrier\ExternalExport\Application\Maileva\MailevaContactExporter;
use MaarchCourrier\ExternalExport\Domain\Maileva\MailevaTemplate;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\MailevaApiServiceInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaApiCouldNotAddRecipientProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaApiCouldNotCreateDocumentProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaApiCouldNotCreateSendingProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaApiCouldNotDownloadDepositProofProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaApiCouldNotDownloadProofOfReceiptProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaApiCouldNotRetrieveSendersProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaApiCouldNotSetOptionsProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaApiUnableToDeleteSendingProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaApiUnableToSubmitSendingProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaCouldNotGetAuthTokenProblem;
use Psr\Log\LoggerInterface;
use SrcCore\controllers\PasswordController;
use SrcCore\models\CurlModel;
use SrcCore\models\TextFormatModel;

class MailevaApiService implements MailevaApiServiceInterface
{
    private string $connectionUri;
    private string $clientId;
    private string $clientSecret;
    private MailevaTemplate $template;
    private string $token = '';
    private string $baseApiUri;
    private string $urlComplement = 'mail/v2';
    private int $documentPosition = 1;
    private bool $isEreMode = false;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MailevaContactExporter $mailevaContactExporter
    ) {
    }

    public function setConfig(array $mailevaConfig, MailevaTemplate $template): void
    {
        $this->connectionUri = $mailevaConfig['connectionUri'];
        $this->clientId = $mailevaConfig['clientId'];
        $this->clientSecret = $mailevaConfig['clientSecret'];
        $this->baseApiUri = $mailevaConfig['uri'];
        $this->template = $template;

        if (str_contains($template->getOptions()['sendMode'] ?? '', 'digital_registered_mail')) {
            $this->urlComplement = 'registered_mail/v4';
        } elseif (str_contains($template->getOptions()['sendMode'] ?? '', 'ere')) {
            $this->urlComplement = 'secured_electronic_mail/v1';
            $this->isEreMode = true;
        }
    }

    /**
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function getAuthToken(): void
    {
        $data = [
            'grant_type' => 'password',
            'username'   => $this->template->getAccount()['id'],
            'password'   => PasswordController::decrypt([
                'encryptedData' => $this->template->getAccount()['password']
            ]),
        ];

        $body = http_build_query($data);

        $curlResponse = CurlModel::exec([
            'url'       => "$this->connectionUri/auth/realms/services/protocol/openid-connect/token",
            'basicAuth' => ['user' => $this->clientId, 'password' => $this->clientSecret],
            'headers'   => ['Content-Type: application/x-www-form-urlencoded'],
            'method'    => 'POST',
            'body'      => $body
        ]);

        if ($curlResponse['code'] != 200) {
            $this->logger->error(
                'Maileva authentication failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaCouldNotGetAuthTokenProblem($curlResponse['errors'], $curlResponse['code']);
        }

        $this->token = $curlResponse['response']['access_token'];
    }

    /**
     * @throws MailevaApiCouldNotCreateSendingProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function createSending(array $body): string
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $curlResponse = CurlModel::exec([
            'url'        => "$this->baseApiUri/$this->urlComplement/sendings",
            'bearerAuth' => ['token' => $this->token],
            'headers'    => ['Content-Type: application/json'],
            'method'     => 'POST',
            'body'       => json_encode($body)
        ]);

        if ($curlResponse['code'] != 201) {
            $this->logger->error(
                'Maileva create sending failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaApiCouldNotCreateSendingProblem($curlResponse['errors'], $curlResponse['code']);
        }

        return $curlResponse['response']['id'];
    }

    /**
     * @throws MailevaApiCouldNotCreateDocumentProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function setDocumentForSending(string $sendingId, string $resourceName, string $fileContent): void
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $filename = TextFormatModel::formatFilename(['filename' => $resourceName]);

        $curlResponse = CurlModel::exec([
            'url'           => "$this->baseApiUri/$this->urlComplement/sendings/$sendingId/documents",
            'bearerAuth'    => ['token' => $this->token],
            'method'        => 'POST',
            'multipartBody' => [
                'document' => [
                    'isFile'   => true,
                    'filename' => "$filename.pdf",
                    'content'  => $fileContent,
                ],
                'metadata' => ['priority' => $this->documentPosition, 'name' => $resourceName]
            ]
        ]);
        $this->documentPosition++;

        if ($curlResponse['code'] != 201) {
            $errors = $curlResponse['errors'] ?? '';
            if ($curlResponse['code'] == 400) {
                foreach ($curlResponse['response']['errors'] as $error) {
                    $errors = "$errors. {$error['code']} : {$error['message']}.";
                }
            }

            $this->logger->error(
                'Maileva document creation failed',
                [
                    'httpCode' => $curlResponse['code'],
                    'errors'   => [$curlResponse['errors'] ?? [], $curlResponse['response']['errors'] ?? []]
                ]
            );
            throw new MailevaApiCouldNotCreateDocumentProblem($errors, $curlResponse['code']);
        }
    }

    /**
     * @param ContactInterface $recipient
     * @return array
     * @throws ContactEmailAddressIsInvalidProblem
     * @throws ContactEmailAddressIsRequiredProblem
     * @throws ContactPostalAddressIsNotCompleteEnoughProblem
     * @throws ContactPostalAddressIsNotInFranceProblem
     */
    private function prepareRecipientForSendingBody(ContactInterface $recipient): array
    {
        if (!$this->isEreMode) {
            $recipientAddress = $this->mailevaContactExporter->buildAfnorAddress($recipient);
            $recipientAddress = explode('|', $recipientAddress);
            return [
                "address_line_1" => $recipientAddress[1],
                "address_line_2" => $recipientAddress[2],
                "address_line_3" => $recipientAddress[3],
                "address_line_4" => $recipientAddress[4],
                "address_line_5" => $recipientAddress[5],
                "address_line_6" => $recipientAddress[6],
                "country_code"   => 'FR'
            ];
        } else {
            $recipientEmail = $this->mailevaContactExporter->getEmailAddress($recipient);
            return [
                "legal_status" => 'INDIVIDUAL',
                "first_name"   => $recipient->getFirstName(),
                "last_name"    => $recipient->getLastName(),
                "email"        => $recipientEmail,
                "company"      => $recipient->getCompany() ?? ''
            ];
        }
    }

    /**
     * @return array
     *  ['recipientId' => "Recipient id", 'acknowledgement_of_receipt_url' => "acknowledgement url of recipient"]
     *
     * @throws MailevaApiCouldNotAddRecipientProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function setRecipientForSending(string $sendingId, ContactInterface $recipient): array
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $body = $this->prepareRecipientForSendingBody($recipient);

        $curlResponse = CurlModel::exec([
            'url'        => "$this->baseApiUri/$this->urlComplement/sendings/$sendingId/recipients",
            'bearerAuth' => ['token' => $this->token],
            'headers'    => ['Content-Type: application/json'],
            'method'     => 'POST',
            'body'       => json_encode($body)
        ]);

        if ($curlResponse['code'] != 201) {
            $this->logger->error(
                'Maileva recipient creation failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaApiCouldNotAddRecipientProblem($curlResponse['errors'], $curlResponse['code']);
        }

        return [
            'recipientId'                    => $curlResponse['response']['id'],
            'acknowledgement_of_receipt_url' => $curlResponse['response']['acknowledgement_of_receipt_url'] ?? null
        ];
    }

    /**
     * @throws MailevaApiCouldNotSetOptionsProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function setSendingOptions(string $sendingId, array $options): void
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $curlResponse = CurlModel::exec([
            'url'     => "$this->baseApiUri/$this->urlComplement/sendings/$sendingId",
            'headers' => ['Authorization: Bearer ' . $this->token, 'Content-Type: application/json'],
            'method'  => 'PATCH',
            'body'    => json_encode($options)
        ]);

        if ($curlResponse['code'] != 200) {
            $this->logger->error(
                'Maileva options modification failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaApiCouldNotSetOptionsProblem($curlResponse['errors'], $curlResponse['code']);
        }
    }

    /**
     * @throws MailevaApiUnableToSubmitSendingProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function send(string $sendingId): void
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $curlResponse = CurlModel::exec([
            'url'        => "$this->baseApiUri/$this->urlComplement/sendings/$sendingId/submit",
            'bearerAuth' => ['token' => $this->token],
            'headers'    => ['Content-Type: application/json'],
            'method'     => 'POST',
        ]);

        if (!in_array($curlResponse['code'], [200, 202], true)) {
            $this->logger->error(
                'Maileva submit sending failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaApiUnableToSubmitSendingProblem($curlResponse['errors'], $curlResponse['code']);
        }
    }

    /**
     * Can delete a sending. Only sendings in draft status can be deleted.
     *
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws MailevaApiUnableToDeleteSendingProblem
     * @throws Exception
     */
    public function deleteSending(string $sendingId): void
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $curlResponse = CurlModel::exec([
            'url'        => "$this->baseApiUri/$this->urlComplement/sendings/$sendingId",
            'bearerAuth' => ['token' => $this->token],
            'headers'    => ['Content-Type: application/json'],
            'method'     => 'DELETE',
        ]);

        if ($curlResponse['code'] != 200) {
            $errors = $curlResponse['errors'] ?? '';
            if ($curlResponse['code'] == 400) {
                foreach ($curlResponse['response']['errors'] as $error) {
                    $errors = "$errors. {$error['code']} : {$error['message']}.";
                }
            }

            $this->logger->error(
                'Maileva delete sending failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaApiUnableToDeleteSendingProblem(
                $sendingId,
                $curlResponse['errors'],
                $curlResponse['code']
            );
        }
    }

    /**
     * @param string $id
     * @return array
     * @throws MailevaApiCouldNotRetrieveSendersProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function getEreSenderDetailById(string $id): array
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $curlResponse = CurlModel::exec([
            'url'        => "$this->baseApiUri/electronic_mail_emitter/v1/senders/$id",
            'bearerAuth' => ['token' => $this->token],
            'headers'    => ['Content-Type: application/json'],
            'method'     => 'GET',
        ]);

        if ($curlResponse['code'] != 200) {
            $errors = $curlResponse['errors'] ?? '';
            if ($curlResponse['code'] == 400) {
                foreach ($curlResponse['response']['errors'] as $error) {
                    $errors = "$errors. {$error['code']} : {$error['message']}.";
                }
            }

            $this->logger->error(
                'Maileva delete sending failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaApiCouldNotRetrieveSendersProblem(
                $curlResponse['errors'],
                $curlResponse['code']
            );
        } else {
            return $curlResponse['response'] ?? [];
        }
    }

    /**
     * @return array
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function getEreSenders(): array
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $curlResponse = CurlModel::exec([
            'url'        => "$this->baseApiUri/electronic_mail_emitter/v1/senders?start_index=1&count=20",
            'bearerAuth' => ['token' => $this->token],
            'headers'    => ['Content-Type: application/json'],
            'method'     => 'GET',
        ]);

        if ($curlResponse['code'] != 200) {
            $errors = $curlResponse['errors'] ?? '';
            if ($curlResponse['code'] == 400) {
                foreach ($curlResponse['response']['errors'] as $error) {
                    $errors = "$errors. {$error['code']} : {$error['message']}.";
                }
            }

            $this->logger->error(
                'Maileva delete sending failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaApiCouldNotRetrieveSendersProblem(
                $curlResponse['errors'],
                $curlResponse['code']
            );
        } else {
            return $curlResponse['response']['senders'] ?? [];
        }
    }

    /**
     * @param string $sendingId
     * @param string $recipientId
     * @return string[]
     * @throws MailevaApiCouldNotDownloadDepositProofProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function downloadDepositProof(string $sendingId, string $recipientId): array
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $url = "$this->baseApiUri/secured_electronic_mail/v1" .
            "/sendings/$sendingId/recipients/$recipientId/download_deposit_proof";
        $curlResponse = CurlModel::exec([
            'url'        => $url,
            'bearerAuth' => ['token' => $this->token],
            'headers'    => ['Content-Type: application/pdf', 'Accept: application/pdf'],
            'method'     => 'GET'
        ]);

        if ($curlResponse['code'] != 200) {
            $this->logger->error(
                'Maileva retrieve deposit proof failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaApiCouldNotDownloadDepositProofProblem($curlResponse['errors'], $curlResponse['code']);
        }

        return $curlResponse;
    }

    /**
     * @param string $sendingId
     * @param string $recipientId
     * @return array
     * @throws MailevaApiCouldNotDownloadProofOfReceiptProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function downloadProofOfReceipt(string $sendingId, string $recipientId): array
    {
        if (empty($this->token)) {
            $this->getAuthToken();
        }

        $url = "$this->baseApiUri/secured_electronic_mail/v1" .
            "/sendings/$sendingId/recipients/$recipientId/download_delivery_proof";
        $curlResponse = CurlModel::exec([
            'url'        => $url,
            'bearerAuth' => ['token' => $this->token],
            'headers'    => ['Content-Type: application/pdf', 'Accept: application/pdf'],
            'method'     => 'GET'
        ]);

        if ($curlResponse['code'] != 200) {
            $this->logger->error(
                'Maileva retrieve proof of receipt failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );
            throw new MailevaApiCouldNotDownloadProofOfReceiptProblem($curlResponse['errors'], $curlResponse['code']);
        }

        return $curlResponse;
    }
}
