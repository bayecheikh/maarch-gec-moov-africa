<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Api Could Not Update Workflow Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagApiCouldNotUpdateWorkflowProblem extends Problem
{
    public function __construct(string $detail, int $status)
    {
        parent::__construct(
            _GOODFLAG_API_COULD_NOT_UPDATE_WORKFLOW_ . " : $detail",
            $status,
            [
                'error' => $detail
            ],
            'goodflagApiCouldNotUpdateWorkflow'
        );
    }
}
