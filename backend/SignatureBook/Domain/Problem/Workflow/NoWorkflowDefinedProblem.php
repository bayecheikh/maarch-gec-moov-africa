<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief No Workflow Defined Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem\Workflow;

use MaarchCourrier\Core\Domain\Problem\Problem;

class NoWorkflowDefinedProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _NO_WORKFLOW_DEFINED,
            400,
            lang: "noWorkflowDefined"
        );
    }
}
