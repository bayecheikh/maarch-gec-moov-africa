<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Goodflag Workflow Database Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port;

interface GoodflagWorkflowDatabaseServiceInterface
{
    /**
     * Enregistre les informations du workflow Goodflag dans la BDD
     *
     * @param int $resId L'ID de la ressource
     * @param string $collId Collection (letterbox_coll ou attachments_coll)
     * @param string $workflowId ID du workflow Goodflag
     * @param array $workflowData Données du workflow
     * @return void
     */
    public function saveWorkflowInfosToDatabase(
        int $resId,
        string $collId,
        string $workflowId,
        array $workflowData
    ): void;

    public function retrieveResourcesByWorkflowId(string $workflowId): array;

    public function updateWorkflowStateInDatabase(
        string $workflowId,
        array $workflowData,
        ?string $lastSignatoryLabel = null
    ): void;
}
