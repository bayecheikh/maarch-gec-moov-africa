<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Could Not Get Ixbus Natures From Api Service Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem;

use MaarchCourrier\Core\Domain\Port\CurlErrorInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;

class CouldNotGetIxbusNaturesFromApiServiceProblem extends Problem
{
    public function __construct(CurlErrorInterface $curlError)
    {
        parent::__construct(
            "Could not get Ixbus Natures from api service: {$curlError->getMessage()}",
            $curlError->getCode()
        );
    }
}
