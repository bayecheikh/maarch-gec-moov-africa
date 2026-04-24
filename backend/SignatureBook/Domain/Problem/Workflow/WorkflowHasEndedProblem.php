<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Workflow Has Ended Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem\Workflow;

use MaarchCourrier\Core\Domain\Problem\Problem;

class WorkflowHasEndedProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _WORKFLOW_IS_COMPLETE,
            400,
            lang: "workflowHasEnded"
        );
    }
}
