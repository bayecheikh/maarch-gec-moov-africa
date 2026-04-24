<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Duplicate Config Instance Existence Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class IxbusDuplicatedInstanceIdConfigProblem extends Problem
{
    public function __construct(string $instanceId)
    {
        parent::__construct(
            "Ixbus duplicated instance configuration: An instance.id already exists.",
            400,
            [
                'instanceId' => $instanceId
            ],
            'ixbusDuplicatedInstanceConfig'
        );
    }
}
