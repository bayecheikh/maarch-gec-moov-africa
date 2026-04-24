<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Retrieve Config Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Ixbus\Factory;

use MaarchCourrier\ExternalSignatureBook\Application\Ixbus\RetrieveConfig;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\ExternalSignatureBookConfigService;

class IxbusRetrieveConfigFactory
{
    public static function create(): RetrieveConfig
    {
        return new RetrieveConfig(
            new ExternalSignatureBookConfigService()
        );
    }
}
