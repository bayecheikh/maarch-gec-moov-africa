<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Create Or Update SignatureBook Watermark Configuration Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory\Watermark;

use MaarchCourrier\Core\Domain\SignatureBook\Port\CreateOrUpdateSignatureBookWatermarkConfigFactoryInterface;
use MaarchCourrier\Core\Infrastructure\Curl\CurlService;
use MaarchCourrier\SignatureBook\Application\Watermark\CreateOrUpdateSignatureBookWatermarkConfiguration;
use MaarchCourrier\SignatureBook\Infrastructure\Controller\SignatureBookWatermarkConfigurationService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;

class CreateOrUpdateSignatureBookWatermarkConfigFactory implements
    CreateOrUpdateSignatureBookWatermarkConfigFactoryInterface
{
    public function create(): CreateOrUpdateSignatureBookWatermarkConfiguration
    {
        return new CreateOrUpdateSignatureBookWatermarkConfiguration(
            new SignatureBookWatermarkConfigurationService(
                new SignatureServiceJsonConfigLoader(),
                new CurlService()
            )
        );
    }
}
