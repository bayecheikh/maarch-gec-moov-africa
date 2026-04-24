<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Could Not Get Ixbus Editors Of Nature From Api Service Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem;

use MaarchCourrier\Core\Domain\Port\CurlErrorInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;

class CouldNotGetIxbusEditorUsersFromNatureApiServiceProblem extends Problem
{
    public function __construct(CurlErrorInterface $curlError, string $natureId)
    {
        parent::__construct(
            "Could not get Ixbus editor users from nature api service: {$curlError->getMessage()}",
            $curlError->getCode(),
            [
                'natureId' => $natureId
            ]
        );
    }
}
