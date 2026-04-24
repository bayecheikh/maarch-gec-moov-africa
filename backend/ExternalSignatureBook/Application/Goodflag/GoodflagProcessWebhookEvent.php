<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Process Webhook Event class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use DateTime;
use DateTimeImmutable;
use Exception;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagWorkflow;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagWorkflowDatabaseServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagWehbookEventNotValid;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagWorkflowIdNotFound;
use MaarchCourrier\History\Application\AddHistoryRecord;
use MaarchCourrier\Note\Domain\Note;
use MaarchCourrier\Note\Domain\Port\NoteRepositoryInterface;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;

class GoodflagProcessWebhookEvent
{
    public function __construct(
        private readonly GoodflagApiServiceInterface $goodflagApiService,
        private readonly GoodflagCheckWebhookEvent $checkWebhookEvent,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly GoodflagWorkflowDatabaseServiceInterface $workflowDatabaseService,
        private readonly AddHistoryRecord $addHistory,
        private readonly NoteRepositoryInterface $noteRepository,
        private readonly GoodflagRetrieveAndSaveSignedFile $goodflagRetrieveAndSaveSignedFile
    ) {
    }


    /**
     * @param string $webhookEventId
     * @param string $eventType
     * @param int $timestampEvent
     * @return void
     * @throws GoodflagWehbookEventNotValid
     * @throws GoodflagWorkflowIdNotFound
     * @throws ResourceDoesNotExistProblem
     * @throws Exception
     */
    public function execute(string $webhookEventId, string $eventType, int $timestampEvent): void
    {
        if ($this->checkWebhookEvent->isValid($webhookEventId)) {
            $this->goodflagApiService->loadConfig();

            $webhookEvent = $this->goodflagApiService->retrieveWebhookEvent($webhookEventId);
            $historyEvent = null;
            if ($eventType === 'recipientRefused') {
                $historyEvent = $this->processRefuseEvent($webhookEvent['workflowId']);
            } elseif ($eventType === 'recipientFinished') {
                $historyEvent = $this->processSignEvent($webhookEvent['workflowId']);
            } elseif ($eventType === 'workflowStopped') {
                $historyEvent = $this->processWorkflowStoppedEvent($webhookEvent['workflowId']);
            } elseif ($eventType === 'workflowFinished') {
                $historyEvent = $this->processWorkflowFinishedEvent($webhookEvent['workflowId']);
            }

            if (!empty($historyEvent)) {
                $superAdmin = (new UserRepository())->getSuperAdmin();
                $eventDate = (new DateTime())
                    ->setTimestamp($timestampEvent / 1000)
                    ->format('c');

                $this->addHistory->add(
                    tableName: $historyEvent['tableName'],
                    recordId: $historyEvent['resId'],
                    eventId: '1',
                    eventType: 'ACTION#1',
                    info: $historyEvent['message'],
                    eventDate: $eventDate,
                    user: $superAdmin
                );
            }
        } else {
            throw new GoodflagWehbookEventNotValid($webhookEventId);
        }
    }


    /**
     * @param string $workflowId
     * @return array
     * @throws GoodflagWorkflowIdNotFound
     * @throws Exception
     */
    private function processRefuseEvent(string $workflowId): array
    {
        $workflow = $this->goodflagApiService->retrieveWorkflow($workflowId);

        $relativeStep = [];
        foreach ($workflow['steps'] as $numStep => $step) {
            for ($i = count($step['logs']) - 1; $i >= 0; $i--) {
                $log = $step['logs'][$i];
                if ($log['operation'] === 'refuse') {
                    $relativeStep = [
                        'step'            => $numStep + 1,
                        'timestamp'       => $log['created'],
                        'recipientEmail'  => $log['recipientEmail'],
                        'recipientUserId' => $log['recipientUserId'] ?? null,
                        'noteContent'     => $log['reason']
                    ];
                    break;
                }
            }
        }

        $documents = $this->workflowDatabaseService->retrieveResourcesByWorkflowId($workflowId);
        if (isset($documents['resources']) && count($documents['resources']) > 0) {
            $mainResource = $this->mainResourceRepository->getMainResourceByResId($documents['resources'][0]['res_id']);
            $currentWorkflow = $mainResource->getExternalState()['signatureBookWorkflow'];
            $resIdMainResource = $mainResource->getResId();
        } elseif (isset($documents['attachments']) && count($documents['attachments']) > 0) {
            $attachment = $this->attachmentRepository->getAttachmentByResId($documents['attachments'][0]['res_id']);
            $currentWorkflow = $attachment->getExternalState()['signatureBookWorkflow'];
            $resIdMainResource = $attachment->getMainResource()->getResId();
        } else {
            throw new GoodflagWorkflowIdNotFound($workflowId);
        }

        foreach ($currentWorkflow['workflow'] as $key => $workflowStep) {
            if (
                $workflowStep['step'] === $relativeStep['step'] &&
                ((isset($workflowStep['id']) && $workflowStep['id'] === $relativeStep['recipientUserId']) ||
                    (isset($workflowStep['email']) && $workflowStep['email'] === $relativeStep['recipientEmail']))
            ) {
                $currentWorkflow['workflow'][$key]['status'] = 'refused';
                $currentWorkflow['workflow'][$key]['processedDate'] = (new DateTime())
                    ->setTimestamp(intval($relativeStep['timestamp']) / 1000)
                    ->format('c');
                $currentWorkflow['workflow'][$key]['note'] = $relativeStep['noteContent'];

                if (!empty($workflowStep['firstname']) || !empty($workflowStep['lastname'])) {
                    $relativeStep['recipientName'] =
                        trim($workflowStep['firstname'] . ' ' . $workflowStep['lastname']);
                } elseif (!empty($workflowStep['id'])) {
                    $goodflagUser = $this->goodflagApiService->retrieveSpecificUser($workflowStep['id']);
                    $relativeStep['recipientName'] = $goodflagUser['firstName'] . ' ' . $goodflagUser['lastName'];
                }
                break;
            }
        }

        $this->workflowDatabaseService->updateWorkflowStateInDatabase(
            $workflowId,
            $currentWorkflow['workflow']
        );

        $eventDate = (new DateTime())
            ->setTimestamp(intval($relativeStep['timestamp']) / 1000)
            ->format('c');

        if (!empty($relativeStep['noteContent'])) {
            $superAdmin = (new UserRepository())->getSuperAdmin();
            $note = (new Note())
                ->setIdentifier($resIdMainResource)
                ->setCreator($superAdmin)
                ->setCreationDate(new DateTimeImmutable($eventDate))
                ->setNoteText(
                    "[Goodflag] Motif de refus par {$relativeStep['recipientName']} " .
                    ": {$relativeStep['noteContent']}"
                );

            $this->noteRepository->add($note);
        }

        return [
            'tableName' => 'res_letterbox',
            'resId'     => $resIdMainResource,
            'message'   => '[Goodflag] Refus de signature du parapheur par ' . $relativeStep['recipientName']
        ];
    }

    /**
     * Build a summary of operations from a workflow payload.
     *
     * Expected shape:
     * $workflow = [
     *   ...,
     *   'steps' => [
     *     [
     *       'logs' => [
     *         [
     *           'operation' => 'sign'|'uninvite'|...,
     *           'created' => 1762353183797,
     *           'recipientEmail' => 'user@example.com',
     *           'recipientUserId' => 123|null
     *         ],
     *         ...
     *       ]
     *     ],
     *     ...
     *   ]
     * ];
     *
     * @param array $workflow
     * @return array{
     *   sign?: array{step:int,timestamp:string|null,recipientEmail:string|null,recipientUserId:int|string|null},
     *   uninvite?: list<array{
     *     step:int,timestamp:string|null,recipientEmail:string|null,recipientUserId:int|string|null
     *   }>
     * }
     */
    private function mapRelativeStepPerOperationForSignEvent(array $workflow): array
    {
        $steps = $workflow['steps'] ?? [];
        $result = [];

        // Helper: find the last occurrence of an operation within a step's logs (scan from the end)
        $findLastInStep = static function (array $logs, string $operation): ?array {
            for ($i = count($logs) - 1; $i >= 0; $i--) {
                $log = $logs[$i];
                if (($log['operation'] ?? null) === $operation) {
                    return $log;
                }
            }
            return null;
        };

        foreach ($steps as $numStep => $step) {
            $logs = $step['logs'] ?? [];

            // Capture the last 'sign' found while iterating steps
            $lastSign = $findLastInStep($logs, 'sign');
            if ($lastSign !== null) {
                $result['sign'] = [
                    'step'            => $numStep + 1,
                    'timestamp'       => $lastSign['created'] ?? null,
                    'recipientEmail'  => $lastSign['recipientEmail'] ?? null,
                    'recipientUserId' => $lastSign['recipientUserId'] ?? null,
                ];
            }

            // Collect all 'uninvite' logs from earliest → latest
            foreach ($logs as $log) {
                if (($log['operation'] ?? null) === 'uninvite') {
                    $result['uninvite'][] = [
                        'step'            => $numStep + 1,
                        'timestamp'       => $log['created'] ?? null,
                        'recipientEmail'  => $log['recipientEmail'] ?? null,
                        'recipientUserId' => $log['recipientUserId'] ?? null,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @param string $workflowId
     * @return array
     * @throws GoodflagWorkflowIdNotFound
     */
    private function processSignEvent(string $workflowId): array
    {
        $workflow = $this->goodflagApiService->retrieveWorkflow($workflowId);
        $relative = $this->mapRelativeStepPerOperationForSignEvent($workflow);

        $documents = $this->workflowDatabaseService->retrieveResourcesByWorkflowId($workflowId);
        if (!empty($documents['resources'])) {
            $mainResource   = $this->mainResourceRepository->getMainResourceByResId(
                $documents['resources'][0]['res_id']
            );
            $currentWorkflow = $mainResource->getExternalState()['signatureBookWorkflow'];
            $resIdSigned     = $mainResource->getResId();
        } elseif (!empty($documents['attachments'])) {
            $attachment      = $this->attachmentRepository->getAttachmentByResId(
                $documents['attachments'][0]['res_id']
            );
            $currentWorkflow = $attachment->getExternalState()['signatureBookWorkflow'];
            $resIdSigned     = $attachment->getMainResource()->getResId();
        } else {
            throw new GoodflagWorkflowIdNotFound($workflowId);
        }

        $sign      = $relative['sign'];
        $uninvites = $relative['uninvite']  ?? [];

        foreach ($currentWorkflow['workflow'] as $i => $step) {
            $stepNum  = $step['step'];
            $stepId   = $step['id']    ?? null;
            $stepMail = $step['email'] ?? null;

            // Mark the signed step
            if (
                $stepNum === $sign['step'] &&
                (
                    ($stepId && $stepId === ($sign['recipientUserId'] ?? null)) ||
                    ($stepMail && $stepMail === ($sign['recipientEmail'] ?? null))
                )
            ) {
                $currentWorkflow['workflow'][$i]['status'] = 'signed';
                $currentWorkflow['workflow'][$i]['processedDate'] = (new DateTimeImmutable())
                    ->setTimestamp(((int)$sign['timestamp']) / 1000)
                    ->format('c');

                $sign['recipientName'] = trim(($step['firstname'] ?? '') . ' ' . ($step['lastname'] ?? ''));
                if (empty($sign['recipientName']) && !empty($stepId)) {
                    $user = $this->goodflagApiService->retrieveSpecificUser($stepId);
                    $sign['recipientName'] = trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''));
                }
                continue;
            }

            // Mark skipped (uninvited) steps
            foreach ($uninvites as $u) {
                if (
                    $u['step'] === $stepNum &&
                    (
                        ($stepId && $stepId === ($u['recipientUserId'] ?? null)) ||
                        ($stepMail && $stepMail === ($u['recipientEmail'] ?? null))
                    )
                ) {
                    $currentWorkflow['workflow'][$i]['status'] = 'signRequestCancelled';
                    $currentWorkflow['workflow'][$i]['processedDate'] = (new DateTimeImmutable())
                        ->setTimestamp(((int)$u['timestamp']) / 1000)
                        ->format('c');
                    break;
                }
            }
        }

        $this->workflowDatabaseService->updateWorkflowStateInDatabase(
            $workflowId,
            $currentWorkflow['workflow'],
            $sign['recipientName']
        );

        return [
            'tableName' => 'res_letterbox',
            'resId'     => $resIdSigned,
            'message'   => '[Goodflag] Signature du parapheur par ' . $sign['recipientName'],
        ];
    }

    /**
     * @param string $workflowId
     * @return array
     * @throws GoodflagWorkflowIdNotFound
     */
    private function processWorkflowStoppedEvent(string $workflowId): array
    {
        $documents = $this->workflowDatabaseService->retrieveResourcesByWorkflowId($workflowId);
        if (isset($documents['resources']) && count($documents['resources']) > 0) {
            $mainResource = $this->mainResourceRepository->getMainResourceByResId($documents['resources'][0]['res_id']);
            $resIdMainResource = $mainResource->getResId();
        } elseif (isset($documents['attachments']) && count($documents['attachments']) > 0) {
            $attachment = $this->attachmentRepository->getAttachmentByResId($documents['attachments'][0]['res_id']);

            if ($attachment->getStatus() === 'FRZ') {
                $this->attachmentRepository->updateAttachment($attachment, ['status' => 'A_TRA']);
            }

            $mainResource = $attachment->getMainResource();
            $resIdMainResource = $mainResource->getResId();
        } else {
            throw new GoodflagWorkflowIdNotFound($workflowId);
        }

        $status = $this->goodflagApiService->getConfig()->getOptions()['workflowStoppedStatus'] ?? null;
        if ($status) {
            $this->mainResourceRepository->updateMainResourceStatus($mainResource, $status);
        }

        return [
            'tableName' => 'res_letterbox',
            'resId'     => $resIdMainResource,
            'message'   => '[Goodflag] Parapheur stoppé'
        ];
    }

    /**
     * @param string $workflowId
     * @return array
     * @throws ResourceDoesNotExistProblem
     */
    private function processWorkflowFinishedEvent(string $workflowId): array
    {
        $workflow = (new GoodflagWorkflow())->setId($workflowId);
        $mainResource = $this->goodflagRetrieveAndSaveSignedFile->execute($workflow);

        $this->goodflagApiService->archiveWorkflow((new GoodflagWorkflow())->setId($workflowId));

        $status = $this->goodflagApiService->getConfig()->getOptions()['workflowFinishedStatus'] ?? null;
        if ($status) {
            $this->mainResourceRepository->updateMainResourceStatus($mainResource, $status);
        }

        return [
            'tableName' => 'res_letterbox',
            'resId'     => $mainResource->getResId(),
            'message'   => '[Goodflag] Parapheur terminé'
        ];
    }
}
