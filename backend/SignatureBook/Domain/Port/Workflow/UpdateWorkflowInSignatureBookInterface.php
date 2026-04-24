<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Update Workflow In SignatureBook Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port\Workflow;

use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookResourceInterface;

interface UpdateWorkflowInSignatureBookInterface
{
    /**
     * @param array $resourcesWithListInstance
     * @param SignatureBookResourceInterface[] $resourcesToUpdate
     */
    public function update(array $resourcesWithListInstance, array $resourcesToUpdate = []): void;
}
