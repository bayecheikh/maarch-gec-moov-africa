<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Common External Signature Book Api Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Port;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\WorkflowItem;

interface CommonExternalSignatureBookApiServiceInterface
{
    public function getName(): string;

    public function loadConfig(): self;

    /**
     * @param MainResourceInterface|AttachmentInterface $resource
     *
     * @return WorkflowItem[]
     */
    public function fetchDocumentWorkflow(MainResourceInterface|AttachmentInterface $resource): array;
}
