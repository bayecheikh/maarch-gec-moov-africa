<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Workflow Database Service class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Service;

use Attachment\models\AttachmentModel;
use DateTime;
use Exception;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagWorkflowDatabaseServiceInterface;
use Psr\Log\LoggerInterface;
use Resource\models\ResModel;

class GoodflagWorkflowDatabaseService implements GoodflagWorkflowDatabaseServiceInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @throws Exception
     */
    public function saveWorkflowInfosToDatabase(
        int $resId,
        string $collId,
        string $workflowId,
        array $workflowData
    ): void {
        $externalId = [
            'goodflag' => $workflowId,
        ];

        $externalState = [
            'signatureBookWorkflow' => [
                'workflow'  => $workflowData,
                'fetchDate' => (new DateTime())->format('c')
            ]
        ];

        if ($collId === 'letterbox_coll') {
            ResModel::update([
                'set'   => [
                    'external_id'    => json_encode($externalId),
                    'external_state' => json_encode($externalState)
                ],
                'where' => ['res_id = ?'],
                'data'  => [$resId]
            ]);
        } elseif ($collId === 'attachments_coll') {
            AttachmentModel::update([
                'set'   => [
                    'external_id'    => json_encode($externalId),
                    'external_state' => json_encode($externalState)
                ],
                'where' => ['res_id = ?'],
                'data'  => [$resId]
            ]);
        }

        $this->logger->info(
            '[Goodflag API] Workflow infos saved to database',
            [
                'resId'      => $resId,
                'collId'     => $collId,
                'workflowId' => $workflowId
            ]
        );
    }

    /**
     * @param string $workflowId
     * @param array $workflowData
     * @param string|null $lastSignatoryLabel
     * @return void
     * @throws Exception
     */
    public function updateWorkflowStateInDatabase(
        string $workflowId,
        array $workflowData,
        ?string $lastSignatoryLabel = null
    ): void {
        $externalState = [
            'signatureBookWorkflow' => [
                'workflow'  => $workflowData,
                'fetchDate' => (new DateTime())->format('c')
            ]
        ];

        if ($lastSignatoryLabel) {
            $externalState['signatoryUser'] = $lastSignatoryLabel;
        }

        ResModel::update([
            'set'   => [
                'external_state' => json_encode($externalState)
            ],
            'where' => ['external_id->>\'goodflag\' = ?'],
            'data'  => [$workflowId]
        ]);

        AttachmentModel::update([
            'set'   => [
                'external_state' => json_encode($externalState)
            ],
            'where' => ['external_id->>\'goodflag\' = ?'],
            'data'  => [$workflowId]
        ]);

        $this->logger->info(
            '[Goodflag API] Workflow data updated to database',
            [
                'workflowId' => $workflowId
            ]
        );
    }

    /**
     * @param string $workflowId
     * @return array
     * @throws Exception
     */
    public function retrieveResourcesByWorkflowId(string $workflowId): array
    {
        $resources = [];

        $resources['resources'] = ResModel::get([
            'select' => ['res_id', 'external_id->>\'signatureBookId\' as documentId'],
            'where'  => ['external_id->>\'goodflag\' = ?'],
            'data'   => [$workflowId]
        ]);

        $resources['attachments'] = AttachmentModel::get([
            'select' => ['res_id', 'external_id->>\'signatureBookId\' as documentId'],
            'where'  => ['external_id->>\'goodflag\' = ?'],
            'data'   => [$workflowId]
        ]);

        return $resources;
    }
}
