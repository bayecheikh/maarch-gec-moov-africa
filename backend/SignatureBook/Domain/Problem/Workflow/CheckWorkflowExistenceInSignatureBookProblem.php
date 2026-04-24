<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Check Workflow Existence In Signature Book Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem\Workflow;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CheckWorkflowExistenceInSignatureBookProblem extends Problem
{
    public function __construct(string $detail)
    {
        parent::__construct(
            _CHECK_WORKFLOW_EXIST_ERROR . " : $detail",
            400,
            [
                'error' => $detail
            ]
        );
    }
}
