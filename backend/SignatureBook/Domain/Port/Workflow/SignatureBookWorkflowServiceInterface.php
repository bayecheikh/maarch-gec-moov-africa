<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Book Workflow Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port\Workflow;

use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

interface SignatureBookWorkflowServiceInterface
{
    /**
     * @param SignatureBookServiceConfig $config
     *
     * @return void
     */
    public function setConfig(SignatureBookServiceConfig $config): void;
    /**
     * @param SignatureBookResource $resource
     *
     * @return bool|array
     */
    public function doesWorkflowExists(SignatureBookResource $resource): bool|array;
    /**
     * @param SignatureBookResource $resource
     * @param array $workflow
     *
     * @return bool|array
     */
    public function updateWorkflow(SignatureBookResource $resource, array $workflow): bool|array;
    /**
     * @param SignatureBookResource $resource
     *
     * @return bool|array
     */
    public function interruptWorkflow(SignatureBookResource $resource): bool|array;
    /**
     * @param SignatureBookResource $resource
     *
     * @return bool|array
     */
    public function deleteResource(SignatureBookResource $resource): bool|array;
}
