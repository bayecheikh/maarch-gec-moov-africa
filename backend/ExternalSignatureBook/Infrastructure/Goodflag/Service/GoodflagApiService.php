<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Api Service class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Service;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Curl\CurlRequest;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\Port\CurlServiceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagRetrieveConfiguration;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagInstanceConfig;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagConfigInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagInstanceConfigInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagWorkflowInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotAddSignaturePositionsProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotArchiveWorkflowProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotCreateBlobProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotCreatePartsProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotCreateUserProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotCreateWebhookProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotCreateWorkflowProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotDownloadDocumentsProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotDownloadEvidenceCertificateProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotRetrieveConsentPagesProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotRetrieveContactsProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotRetrieveCurrentUserInfosProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotRetrieveSignatureProfilesProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotRetrieveUsersProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotRetrieveWebhookEventProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotRetrieveWebhookProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotRetrieveWorkflowProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotStartWorkflowProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagApiCouldNotUpdateWorkflowProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodFlagExternalSignatureBookIsNotEnabledProblem;
use Psr\Log\LoggerInterface;
use SrcCore\controllers\PasswordController;
use SrcCore\models\CoreConfigModel;

class GoodflagApiService implements GoodflagApiServiceInterface
{
    private GoodflagConfigInterface $goodflagConfig;

    /**
     * @param LoggerInterface $logger
     * @param CurlServiceInterface $curlService
     * @param GoodflagRetrieveConfiguration $goodflagConfiguration
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CurlServiceInterface $curlService,
        private readonly GoodflagRetrieveConfiguration $goodflagConfiguration
    ) {
    }

    public function getName(): string
    {
        return 'Goodflag';
    }

    public function getConfig(): GoodflagConfigInterface
    {
        return $this->goodflagConfig;
    }

    /**
     * @return GoodflagApiServiceInterface
     * @throws GoodFlagExternalSignatureBookIsNotEnabledProblem
     * @throws GoodflagConfigNotFoundProblem
     */
    public function loadConfig(): GoodflagApiServiceInterface
    {
        $this->goodflagConfig = $this->goodflagConfiguration->retrieveAccountConfiguration();
        if (!$this->goodflagConfig->isEnabled()) {
            throw new GoodFlagExternalSignatureBookIsNotEnabledProblem();
        }
        return $this;
    }

    /**
     * @return array
     * @throws GoodflagApiCouldNotRetrieveSignatureProfilesProblem
     * @throws Exception
     */
    public function retrieveSignatureProfiles(): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/signatureProfiles",
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve signature profiles failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotRetrieveSignatureProfilesProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content['items'];
    }

    /**
     * @return array
     * @throws GoodflagApiCouldNotRetrieveConsentPagesProblem
     * @throws Exception
     */
    public function retrieveConsentPages(): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/consentPages",
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve consent pages failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotRetrieveConsentPagesProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content['items'];
    }

    /**
     * @param string|null $search
     * @return array
     * @throws GoodflagApiCouldNotRetrieveContactsProblem
     * @throws Exception
     */
    public function retrieveContacts(?string $search): array
    {
        $filter = (!empty($search)) ? "?text=$search" : "";
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/contacts" . $filter,
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve contacts failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotRetrieveContactsProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content['items'];
    }

    /**
     * @param string|null $search
     * @return array
     * @throws Exception
     */
    public function retrieveUsers(?string $search): array
    {
        $filter = (!empty($search)) ? "&text=$search" : "";

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() .
                "/api/users?items.isDisabled=false&sortBy=items.lastName" . $filter,
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve users failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotRetrieveUsersProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content['items'];
    }


    /**
     * @param string $contactId
     * @return array
     * @throws GoodflagApiCouldNotRetrieveContactsProblem
     * @throws Exception
     */
    public function retrieveSpecificContact(string $contactId): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/contacts/" . $contactId,
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve contacts failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotRetrieveContactsProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param string $userId
     * @return array
     * @throws GoodflagApiCouldNotRetrieveUsersProblem
     * @throws Exception
     */
    public function retrieveSpecificUser(string $userId): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/users/" . $userId,
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve user failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotRetrieveUsersProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param UserInterface $user
     * @return string
     * @throws GoodflagApiCouldNotRetrieveCurrentUserInfosProblem
     * @throws Exception
     */
    public function createNewUser(UserInterface $user): string
    {
        if (empty($this->goodflagConfig->getGroupId())) {
            $this->setCurrentUserInformations();
        }
        $body = [
            "groupId"   => $this->goodflagConfig->getGroupId(),
            "email"     => $user->getMail(),
            "firstName" => $user->getFirstName(),
            "lastName"  => $user->getLastName()
        ];

        if (!empty($user->getPhone())) {
            $phone = str_replace('.', '', $user->getPhone());
            if (preg_match('/^\+(?:[0-9] ?){6,14}[0-9]$/', $phone)) {
                $body['phoneNumber'] = $phone;
            }
        }

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() .
                "/api/tenants/" . $this->goodflagConfig->getTenantId() . "/users",
            'method'     => 'POST',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ]),
            'body'       => $body
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] User creation failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotCreateUserProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content['id'];
    }

    /**
     * @throws GoodflagApiCouldNotRetrieveCurrentUserInfosProblem
     * @throws Exception
     */
    public function setCurrentUserInformations(): void
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/users/me",
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve current user informations failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['errors']]
            );

            throw new GoodflagApiCouldNotRetrieveCurrentUserInfosProblem(
                $content['errors'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        $this->goodflagConfig->setUsrId($content['id']);
        $this->goodflagConfig->setTenantId($content['tenantId']);
        $this->goodflagConfig->setGroupId($content['groupId']);
    }

    /**
     * @param GoodflagWorkflowInterface $workflow
     * @return array
     * @throws GoodflagApiCouldNotCreateWorkflowProblem
     * @throws GoodflagApiCouldNotRetrieveCurrentUserInfosProblem
     * @throws Exception
     */
    public function createWorkflow(GoodflagWorkflowInterface $workflow): array
    {
        if (empty($this->goodflagConfig->getUsrId())) {
            $this->setCurrentUserInformations();
        }

        $steps = [];
        foreach ($workflow->getSteps() as $step) {
            $currentStep = [
                "stepType"         => "signature",
                "recipients"       => [],
                "validityPeriod"   => $this->goodflagConfig->getOptions()['validityPeriod'] ?? 8553600000,
                "invitePeriod"     => $this->goodflagConfig->getOptions()['invitePeriod'] ?? 86400000,
                "maxInvites"       => 1,
                "sendDownloadLink" => true
            ];
            $isSignatureRequired = false;
            foreach ($step as $workflowItem) {
                $currentRecipient = [
                    "consentPageId" => $workflowItem->getConsentPageId()
                ];
                if (!empty($workflowItem->getId())) {
                    $currentRecipient['userId'] = $workflowItem->getId();
                } else {
                    $currentRecipient['firstName'] = $workflowItem->getFirstName();
                    $currentRecipient['lastName'] = $workflowItem->getLastName();
                    $currentRecipient['email'] = $workflowItem->getEmail();
                    if (!empty($workflowItem->getPhone())) {
                        $currentRecipient['phoneNumber'] = $workflowItem->getPhone();
                    }
                    if (!empty($workflowItem->getCountry())) {
                        $currentRecipient['country'] = $workflowItem->getCountry();
                    }
                }

                $currentStep['recipients'][] = $currentRecipient;

                $isSignatureRequired = $workflowItem->isRequired();
            }

            if ($isSignatureRequired) {
                $currentStep['requiredRecipients'] = count($currentStep['recipients']);
            } else {
                $currentStep['requiredRecipients'] = 1;
            }

            $steps[] = $currentStep;
        }

        $body = [
            "name"  => $workflow->getName(),
            "steps" => $steps,
        ];

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() .
                "/api/users/" . $this->goodflagConfig->getUsrId() . "/workflows",
            'method'     => 'POST',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ]),
            'body'       => $body
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Workflow creation failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotCreateWorkflowProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param GoodflagWorkflowInterface $workflow
     * @return array
     * @throws GoodflagApiCouldNotUpdateWorkflowProblem
     * @throws Exception
     */
    public function updateWorkflowOwner(GoodflagWorkflowInterface $workflow): array
    {
        $body = [
            "userId" => $workflow->getUserId(),
        ];

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/workflows/" . $workflow->getId(),
            'method'     => 'PATCH',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ]),
            'body'       => $body
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Workflow update owner failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            //TODO : Modifier le message d'erreur
            throw new GoodflagApiCouldNotUpdateWorkflowProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param GoodflagWorkflowInterface $workflow
     * @return array
     * @throws GoodflagApiCouldNotStartWorkflowProblem
     * @throws Exception
     */
    public function startWorkflow(GoodflagWorkflowInterface $workflow): array
    {
        $body = [
            "workflowStatus" => "started"
        ];

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/workflows/" . $workflow->getId(),
            'method'     => 'PATCH',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ]),
            'body'       => $body
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Workflow start failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotStartWorkflowProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param GoodflagWorkflowInterface $workflow
     * @return array
     * @throws GoodflagApiCouldNotArchiveWorkflowProblem
     * @throws Exception
     */
    public function archiveWorkflow(GoodflagWorkflowInterface $workflow): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/workflows/" . $workflow->getId() . "/archive",
            'method'     => 'PATCH',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Archive workflow failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotArchiveWorkflowProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param string $workflowId
     * @return array
     * @throws GoodflagApiCouldNotRetrieveWorkflowProblem
     * @throws Exception
     */
    public function retrieveWorkflow(string $workflowId): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/workflows/" . $workflowId,
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve workflow failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotRetrieveWorkflowProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param GoodflagWorkflowInterface $workflow
     * @param string $documentPath
     * @return array
     * @throws GoodflagApiCouldNotCreateBlobProblem
     * @throws Exception
     */
    public function createDocumentBlob(
        GoodflagWorkflowInterface $workflow,
        string $documentPath
    ): array {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/workflows/" . $workflow->getId() . "/blobs",
            'method'     => 'POST',
            'headers'    => ['Content-Type: application/octet-stream'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ]),
            'body'       => file_get_contents($documentPath)
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Document blob creation failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotCreateBlobProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param GoodflagWorkflowInterface $workflow
     * @param GoodflagInstanceConfig $templateGoodflag
     * @param string $blobId
     * @param string $documentFilename
     * @param bool $isAnnexDocument
     * @return array
     * @throws GoodflagApiCouldNotCreatePartsProblem
     * @throws Exception
     */
    public function createDocumentPart(
        GoodflagWorkflowInterface $workflow,
        GoodflagInstanceConfigInterface $templateGoodflag,
        string $blobId,
        string $documentFilename,
        bool $isAnnexDocument
    ): array {
        $body = [
            "parts" => [
                [
                    "blobs"       => [$blobId],
                    "contentType" => "application/pdf",
                    "filename"    => $documentFilename
                ]
            ]
        ];

        if ($isAnnexDocument) {
            $ignAttachments = "false";
            $signatureProfileId = "";
        } else {
            $ignAttachments = "true";
            $signatureProfileId = $templateGoodflag->getSignatureProfileId();
        }

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/workflows/" . $workflow->getId() .
                "/blobs/parts?createDocuments=true&ignoreAttachments={$ignAttachments}&unzip=false&convertToPdf=false" .
                "&signatureProfileId=$signatureProfileId",
            'method'     => 'POST',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ]),
            'body'       => $body
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Document parts creation failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotCreatePartsProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param string $documentId
     * @param array $signaturePositions
     * @return bool
     * @throws Exception
     */
    public function addSignaturePositionsToDocumentPart(
        string $documentId,
        array $signaturePositions
    ): bool {
        if (!empty($signaturePositions)) {
            $body = [
                "pdfSignatureFields" => $signaturePositions
            ];

            $curlRequest = new CurlRequest();
            $curlRequest = $curlRequest->createFromArray([
                'url'        => $this->goodflagConfig->getUrl() . "/api/documents/{$documentId}",
                'method'     => 'PATCH',
                'headers'    => ['Content-Type: application/json'],
                'authBearer' => PasswordController::decrypt([
                    'encryptedData' => $this->goodflagConfig->getAccessToken()
                ]),
                'body'       => $body
            ]);

            $curlRequest = $this->curlService->call($curlRequest);
            $content = $curlRequest->getCurlResponse()->getContentReturn();

            if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
                $this->logger->error(
                    '[Goodflag API] Add signature positions failed',
                    ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
                );

                throw new GoodflagApiCouldNotAddSignaturePositionsProblem(
                    $content['error'],
                    $curlRequest->getCurlResponse()->getHttpCode()
                );
            }
        }
        return true;
    }

    /**
     * @param string $endPoint
     * @param array $notifiedEvents
     * @return array
     * @throws GoodflagApiCouldNotCreateWebhookProblem
     * @throws GoodflagApiCouldNotRetrieveCurrentUserInfosProblem
     * @throws Exception
     */
    public function createWebhook(string $endPoint, array $notifiedEvents): array
    {
        if (empty($this->goodflagConfig->getUsrId())) {
            $this->setCurrentUserInformations();
        }

        $body = [
            'isGlobal'       => true,
            'endpointUrl'    => $endPoint,
            'notifiedEvents' => $notifiedEvents,
        ];

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() .
                "/api/tenants/{$this->goodflagConfig->getTenantId()}/webhooks",
            'method'     => 'POST',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ]),
            'body'       => $body
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Webhook creation failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotCreateWebhookProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param string $search
     * @return bool
     * @throws GoodflagApiCouldNotRetrieveWebhookProblem
     * @throws Exception
     */
    public function isWebhookExists(string $search): bool
    {
        $hostname = parse_url($search, PHP_URL_HOST);

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/webhooks?text={$hostname}",
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve webhook failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotRetrieveWebhookProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        foreach ($content['items'] as $webhook) {
            if ($webhook['endpointUrl'] == $search) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $webhookEventId
     *
     * @return array
     * @throws GoodflagApiCouldNotRetrieveWebhookEventProblem
     * @throws GoodflagApiCouldNotRetrieveCurrentUserInfosProblem
     * @throws Exception
     */
    public function retrieveWebhookEvent(string $webhookEventId): array
    {
        if (empty($this->goodflagConfig->getUsrId())) {
            $this->setCurrentUserInformations();
        }

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/webhookEvents/$webhookEventId",
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();


        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Retrieve webhook event failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotRetrieveWebhookEventProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        if ($content['tenantId'] !== $this->goodflagConfig->getTenantId()) {
            $this->logger->error(
                '[Goodflag API] The webhook event is not from the current tenant',
                [
                    'fetchWebhookEventTenantId' => $content['tenantId'],
                    'goodflagConfigTenantId'    => $this->goodflagConfig->getTenantId()
                ]
            );
            throw new GoodflagApiCouldNotRetrieveWebhookEventProblem(
                "The webhook event is not from the current tenant",
                400
            );
        }

        return $content;
    }

    /**
     * @param string $documentId
     * @return array
     * @throws GoodflagApiCouldNotDownloadDocumentsProblem
     * @throws Exception
     */
    public function retrieveDocument(string $documentId): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/documents/" . $documentId,
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/json'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Archive workflow failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotDownloadDocumentsProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param GoodflagWorkflowInterface $workflow
     * @return string
     * @throws GoodflagApiCouldNotDownloadDocumentsProblem
     * @throws Exception
     */
    public function downloadDocuments(GoodflagWorkflowInterface $workflow): string
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/workflows/" . $workflow->getId() .
                "/downloadDocuments",
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/pdf', 'Accept: application/zip'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Archive workflow failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotDownloadDocumentsProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param string $documentId
     * @param string $hashPart
     * @return string
     * @throws GoodflagApiCouldNotDownloadDocumentsProblem
     * @throws Exception
     */
    public function downloadDocumentPart(string $documentId, string $hashPart): string
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/documents/{$documentId}/parts/{$hashPart}",
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/pdf', 'Accept: application/pdf'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Archive workflow failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotDownloadDocumentsProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param GoodflagWorkflowInterface $workflow
     * @return string
     * @throws GoodflagApiCouldNotDownloadEvidenceCertificateProblem
     * @throws Exception
     */
    public function downloadEvidenceCertificate(GoodflagWorkflowInterface $workflow): string
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $this->goodflagConfig->getUrl() . "/api/workflows/" . $workflow->getId() .
                "/downloadEvidenceCertificate",
            'method'     => 'GET',
            'headers'    => ['Content-Type: application/pdf', 'Accept: application/pdf'],
            'authBearer' => PasswordController::decrypt([
                'encryptedData' => $this->goodflagConfig->getAccessToken()
            ])
        ]);

        $curlRequest = $this->curlService->call($curlRequest);
        $content = $curlRequest->getCurlResponse()->getContentReturn();

        if ($curlRequest->getCurlResponse()->getHttpCode() != 200) {
            $this->logger->error(
                '[Goodflag API] Archive workflow failed',
                ['httpCode' => $curlRequest->getCurlResponse()->getHttpCode(), 'errors' => $content['error']]
            );

            throw new GoodflagApiCouldNotDownloadEvidenceCertificateProblem(
                $content['error'],
                $curlRequest->getCurlResponse()->getHttpCode()
            );
        }

        return $content;
    }

    /**
     * @param MainResourceInterface|AttachmentInterface $resource
     *
     * @return array
     * @throws Exception
     * @throws GoodflagApiCouldNotRetrieveUsersProblem
     */
    public function fetchDocumentWorkflow(MainResourceInterface|AttachmentInterface $resource): array
    {
        $knowWorkflow = $resource->getExternalState()['signatureBookWorkflow']['workflow'] ?? [];
        $appTimeZone = CoreConfigModel::getTimezone();

        $externalWorkflow = [];

        foreach ($knowWorkflow as $step) {
            $processDate = empty($step['processedDate']) ?
                null : (new DateTimeImmutable($step['processedDate']))->setTimezone(new DateTimeZone($appTimeZone));

            if (!empty($step['status']) && $step['status'] == 'signed') {
                $status = _SIGNED;
            } elseif (!empty($step['status']) && $step['status'] == 'refused') {
                $status = _REFUSED;
            } elseif (!empty($step['status']) && $step['status'] == 'signRequestCancelled') {
                $status = _SING_REQUEST_CANCELLED;
            } elseif ($processDate == null && empty($step['status'])) {
                $status = _PENDING;
            } else {
                $status = _UNKNOWN_STATUS;
            }

            $workflowItem = [
                'userId'      => null,
                'mode'        => $step['mode'],
                'status'      => $status,
                'order'       => $step['step'],
                'processDate' => $processDate?->format('Y-m-d H:i:s')
            ];

            if ($step['type'] == 'goodflagUser') {
                $goodflagUser = $this->retrieveSpecificUser($step['id']);
                $workflowItem['userDisplay'] = _GOODFLAG_USER . " ({$goodflagUser['name']})";
            } elseif ($step['type'] == 'externalSignatory') {
                $workflowItem['userDisplay'] = _EXTERNAL_USER . " ({$step['firstname']} {$step['lastname']})";
            }
            $externalWorkflow[] = $workflowItem;
        }

        return $externalWorkflow;
    }
}
