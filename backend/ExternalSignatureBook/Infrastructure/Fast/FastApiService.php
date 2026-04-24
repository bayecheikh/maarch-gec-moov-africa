<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Fast Api Service class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Fast;

use Attachment\models\AttachmentModel;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Curl\CurlRequest;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\Port\CurlServiceInterface;
use MaarchCourrier\Core\Domain\Problem\Curl\CurlRequestErrorProblem;
use MaarchCourrier\ExternalSignatureBook\Application\Fast\RetrieveConfig;
use MaarchCourrier\ExternalSignatureBook\Domain\Fast\Port\FastParapheurApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Fast\Port\FastParapheurConfigInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Fast\Problem\FastParapheurExternalSignatureBookIsNotEnabledProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Fast\Problem\FastParapheurMissingConfigProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\CommonExternalSignatureBookApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\WorkflowItem;
use Psr\Log\LoggerInterface;
use Resource\models\ResModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use User\models\UserModel;

class FastApiService implements CommonExternalSignatureBookApiServiceInterface, FastParapheurApiServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RetrieveConfig $retrieveFastConfig,
        private readonly CurlServiceInterface $curlService
    ) {
    }

    private readonly FastParapheurConfigInterface $config;

    public function getName(): string
    {
        return 'Fast Parapheur';
    }

    /**
     * @return FastApiService
     * @throws FastParapheurMissingConfigProblem
     * @throws FastParapheurExternalSignatureBookIsNotEnabledProblem
     */
    public function loadConfig(): FastApiService
    {
        $this->config = $this->retrieveFastConfig->get();
        if (!$this->config->isEnabled()) {
            throw new FastParapheurExternalSignatureBookIsNotEnabledProblem();
        }
        return $this;
    }

    /**
     * @inheritDoc
     * @return WorkflowItem[]
     * @throws CurlRequestErrorProblem
     * @throws Exception
     */
    public function fetchDocumentWorkflow(MainResourceInterface|AttachmentInterface $resource): array
    {
        $fetchDate = new DateTimeImmutable(
            $resource->getExternalState()['signatureBookWorkflow']['fetchDate'] ?? 'now'
        );
        $timeAgo = new DateTimeImmutable('-30 minutes');

        if (
            !empty($resource->getExternalState()['signatureBookWorkflow']['fetchDate']) &&
            !empty($resource->getExternalState()['signatureBookWorkflow']['data']) &&
            $fetchDate->getTimestamp() >= $timeAgo->getTimestamp()
        ) {
            $newDate = $fetchDate->modify('+30 minutes');
            $this->logger->warning("Time limit reached! Next retrieve time : {$newDate->format('d-m-Y H:i')}");
            return $resource->getExternalState()['signatureBookWorkflow']['data'];
        }

        $appTimeZone = CoreConfigModel::getTimezone();

        $knowWorkflow = $resource->getExternalState()['signatureBookWorkflow']['workflow'] ?? [];
        $docHistory = $this->getHistory($resource->getExternalId()['signatureBookId']);
        $fastParapheurUsers = $this->getUsers(format: true);

        $linkedUsers = DatabaseModel::select([
            'select' => [
                'id',
                'concat(firstname, \' \', lastname) as name',
                'external_id->>\'fastParapheur\' as fast_email'
            ],
            'table'  => ['users'],
            'where'  => ['external_id->>\'fastParapheur\' IS NOT NULL']
        ]);

        $assoc = [];
        foreach ($linkedUsers as $u) {
            $fastUser = array_filter($fastParapheurUsers, fn($v) => $v['email'] == $u['fast_email']);
            $fastUser = array_values($fastUser);
            $assoc[$fastUser[0]['idToDisplay']] = [
                'id'           => $u['id'],
                'name'         => $u['name'],
                'fastEmail'    => $fastUser[0]['email']
            ];
        }
        $linkedUsers = $assoc;

        $fastUsers = array_column($fastParapheurUsers, 'email', 'idToDisplay');

        $externalWorkflow = [];
        $workflowOrder = 1;
        $historyStepWasRefused = false;
        $historyStepRefuseDate = null;

        foreach ($docHistory as $step) {
            $mode = $workflowOrder == 1 ? 'init' : null;

            if (
                in_array(
                    $step['stateName'],
                    [
                        $this->config->getValidatedState(),
                        $this->config->getRefusedState(),
                        $this->config->getValidatedVisaState(),
                        $this->config->getRefusedVisaState()
                    ],
                    true
                )
            ) {
                $historyStepWasRefused = in_array(
                    $step['stateName'],
                    [$this->config->getRefusedState(), $this->config->getRefusedVisaState()]
                );
                $mode = in_array(
                    $step['stateName'],
                    [$this->config->getValidatedState(), $this->config->getRefusedState()]
                ) ? 'sign' : 'visa';
            }

            $fastUserFullName  = trim($step['userFullname'] ?? '');
            $isSystemAction = false;

            if (
                $fastUserFullName === '' &&
                !isset($linkedUsers[$fastUserFullName]) && !isset($fastUsers[$fastUserFullName])
            ) {
                // System action
                $isSystemAction = true;
                $fastUserFullName = _SYSTEM_ACTION;
                $mcUser = ['id' => null, 'name' => ''];
            } else {
                $lastKnowWorkflowStep = (count($externalWorkflow) - 1) >= 0 ?
                    $externalWorkflow[count($externalWorkflow) - 1] : null;

                if (
                    isset($linkedUsers[$fastUserFullName]) &&
                    $linkedUsers[$fastUserFullName]['fastEmail'] == $knowWorkflow[0]['id']
                ) {
                    // A known mc user (linked)
                    $mcUser = [
                        'id'   => $linkedUsers[$fastUserFullName]['id'],
                        'name' => "({$linkedUsers[$fastUserFullName]['name']})"
                    ];
                    array_shift($knowWorkflow);
                } elseif (
                    $lastKnowWorkflowStep !== null &&
                    $lastKnowWorkflowStep->isSystem() &&
                    in_array($lastKnowWorkflowStep->getStatus(), ["OTP validé", "OTP signé"]) &&
                    ($knowWorkflow[0]['type'] ?? '') == 'externalOTP' &&
                    "{$knowWorkflow[0]['firstname']} {$knowWorkflow[0]['lastname']}" == $fastUserFullName
                ) {
                    // OTP
                    $fastUserFullName = _EXTERNAL_USER;
                    $mcUser = [
                        'id'   => null,
                        'name' => '(' . $knowWorkflow[0]['firstname'] . ' ' . $knowWorkflow[0]['lastname'] . ')'
                    ];
                    array_shift($knowWorkflow);
                } elseif (
                    isset($fastUsers[$fastUserFullName]) &&
                    $fastUsers[$fastUserFullName] == $knowWorkflow[0]['id']
                ) {
                    // A known fast user (not linked)
                    $mcUser = ['id' => null, 'name' => "($fastUserFullName)"];
                    $fastUserFullName = _PAST_PARAPHEUR_USER;
                    array_shift($knowWorkflow);
                } else {
                    // Unknown user keep the original $fastUserFullName
                    $mcUser = ['id' => null, 'name' => ''];
                }
            }

            $processDate = (new DateTimeImmutable($step['date']))->setTimezone(new DateTimeZone($appTimeZone));
            $historyStepRefuseDate = $historyStepWasRefused ? $processDate : null;

            $externalWorkflow[] = (new WorkflowItem())
                ->setUserId($mcUser['id'])
                ->setUserDisplay(trim($fastUserFullName . ' ' . $mcUser['name'])) // fast username (MC linked username)
                ->setMode($mode)
                ->setStatus($step['stateName'])
                ->setOrder($workflowOrder)
                ->setProcessDate($processDate)
                ->setIsSystem($isSystemAction);
            $workflowOrder++;
        }

        if (!empty($knowWorkflow)) {
            foreach ($knowWorkflow as $step) {
                $modeIsSign = $step['mode'] == 'sign';
                $status = $historyStepWasRefused
                    ? ($modeIsSign ? $this->config->getRefusedState() : $this->config->getRefusedVisaState())
                    : ($modeIsSign ? 'En attente de signature' : 'En attente de visa');


                $workflowItem = (new WorkflowItem())
                    ->setMode($step['mode'])
                    ->setStatus($status)
                    ->setOrder($workflowOrder)
                    ->setProcessDate($historyStepRefuseDate)
                    ->setIsSystem(false);

                if ($step['type'] == 'maarchCourrierUserId') {
                    $user = UserModel::getById([
                        'id' => $step['id'],
                        'select' => [
                            'concat(firstname, \' \', lastname) as name',
                            'external_id->>\'fastParapheur\' as "fastParapheurEmail"'
                        ]
                    ]);

                    if (empty($user['fastParapheurEmail'])) {
                        $userDisplay = $user['name'];
                    } else {
                        $fastUser = array_filter(
                            $fastParapheurUsers,
                            fn($v) => $v['email'] == $user['fastParapheurEmail']
                        );
                        $fastUser = array_values($fastUser);
                        $userDisplay = "{$fastUser[0]['idToDisplay']} ({$user['name']})";
                    }

                    $workflowItem->setUserDisplay($userDisplay);
                } elseif ($step['type'] == 'fastParapheurUserEmail') {
                    $fastUser = array_filter($fastParapheurUsers, fn($v) => $v['email'] == $step['id']);
                    $fastUser = array_values($fastUser);
                    $workflowItem->setUserDisplay(_PAST_PARAPHEUR_USER . " ({$fastUser[0]['idToDisplay']})");
                } elseif ($step['type'] == 'externalOTP') {
                    $workflowItem->setUserDisplay(_EXTERNAL_USER . " ({$step['firstname']} {$step['lastname']})");
                }
                $externalWorkflow[] = $workflowItem;
                $workflowOrder++;
            }
        }

        // Cache the workflow
        // In the future, move the code to its own service for all external sb services
        $currentDate = new DateTimeImmutable();
        $externalState = $resource->getExternalState();
        $externalState['signatureBookWorkflow']['fetchDate'] = $currentDate->format('c');
        $externalState['signatureBookWorkflow']['data'] = $externalWorkflow;
        if ($resource instanceof MainResourceInterface) {
            ResModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$resource->getResId()],
                'postSet' => [
                    'external_state' => 'jsonb_set(external_state, \'{signatureBookWorkflow}\', \'' .
                        json_encode($externalState['signatureBookWorkflow']) . '\'::jsonb)'
                ]
            ]);
        } else {
            AttachmentModel::update([
                'where'   => ['res_id = ?'],
                'data'    => [$resource->getResId()],
                'postSet' => [
                    'external_state' => 'jsonb_set(external_state, \'{signatureBookWorkflow}\', \'' .
                        json_encode($externalState['signatureBookWorkflow']) . '\'::jsonb)'
                ]
            ]);
        }

        return $externalWorkflow;
    }

    /**
     * @inheritDoc
     */
    public function getHistory(string $externalDocumentId): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'       => rtrim($this->config->getUrl(), '/') . "/documents/v2/$externalDocumentId/history",
            'method'    => 'GET',
            'options' => [
                CURLOPT_SSLCERT       => $this->config->getCertPath(),
                CURLOPT_SSLCERTPASSWD => $this->config->getCertPass(),
                CURLOPT_SSLCERTTYPE   => $this->config->getCertType()
            ]
        ]);

        $curlRequest = $this->curlService->call($curlRequest);

        if ($curlRequest->getCurlResponse()->getHttpCode() >= 400) {
            throw new CurlRequestErrorProblem(
                $curlRequest->getCurlResponse()->getHttpCode(),
                $curlRequest->getCurlResponse()->getContentReturn()
            );
        }

        return $curlRequest->getCurlResponse()->getContentReturn();
    }

    /**
     * @inheritDoc
     */
    public function getUsers(bool $format = false): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'       => rtrim($this->config->getUrl(), '/') .
                '/exportUsersData?siren=' . urlencode($this->config->getSubscriberId()),
            'method'    => 'GET',
            'options' => [
                CURLOPT_SSLCERT       => $this->config->getCertPath(),
                CURLOPT_SSLCERTPASSWD => $this->config->getCertPass(),
                CURLOPT_SSLCERTTYPE   => $this->config->getCertType()
            ]
        ]);
        $curlRequest = $this->curlService->call($curlRequest);

        if ($curlRequest->getCurlResponse()->getHttpCode() >= 400) {
            throw new CurlRequestErrorProblem(
                $curlRequest->getCurlResponse()->getHttpCode(),
                $curlRequest->getCurlResponse()->getContentReturn()
            );
        }

        if (empty($curlRequest->getCurlResponse()->getContentReturn()['users'] ?? null)) {
            return [];
        }

        if (!$format) {
            return $curlRequest->getCurlResponse()->getContentReturn()['users'];
        }

        $users = [];
        foreach ($curlRequest->getCurlResponse()->getContentReturn()['users'] as $user) {
            $users[] = [
                'idToDisplay' => trim($user['prenom'] . ' ' . $user['nom']),
                'email'       => trim($user['email'])
            ];
        }

        return $users;
    }
}
