<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Retrieve Instances Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Ixbus\Factory;

use MaarchCourrier\ExternalSignatureBook\Application\Ixbus\RetrieveConfig;
use MaarchCourrier\ExternalSignatureBook\Application\Ixbus\RetrieveInitialization;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\ExternalSignatureBookConfigService;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Ixbus\Service\IxbusApiService;

class IxbusRetrieveInitializationFactory
{
    public static function create(): RetrieveInitialization
    {
        return new RetrieveInitialization(
            new RetrieveConfig(
                new ExternalSignatureBookConfigService()
            ),
            new IxbusApiService()
        );
    }
}
