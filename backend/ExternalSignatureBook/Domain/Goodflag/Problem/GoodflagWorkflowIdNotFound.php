<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Workflow ID Not Found Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagWorkflowIdNotFound extends Problem
{
    public function __construct(string $workflowId)
    {
        parent::__construct(
            _GOODFLAG_WORKFLOW_ID_NOT_FOUND_ . " : $workflowId",
            400,
            [
                'workflowId' => $workflowId
            ],
            lang: 'goodflagWorkflowIdNotFound'
        );
    }
}
