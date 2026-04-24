<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve SignatureBook Watermark Configuration Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory\Watermark;

use MaarchCourrier\Core\Domain\SignatureBook\Port\RetrieveSignatureBookWatermarkConfigFactoryInterface;
use MaarchCourrier\Core\Infrastructure\Curl\CurlService;
use MaarchCourrier\SignatureBook\Application\Watermark\RetrieveSignatureBookWatermarkConfiguration;
use MaarchCourrier\SignatureBook\Infrastructure\Controller\SignatureBookWatermarkConfigurationService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;

class RetrieveSignatureBookWatermarkConfigFactory implements RetrieveSignatureBookWatermarkConfigFactoryInterface
{
    public function create(): RetrieveSignatureBookWatermarkConfiguration
    {
        return new RetrieveSignatureBookWatermarkConfiguration(
            new SignatureBookWatermarkConfigurationService(
                new SignatureServiceJsonConfigLoader(),
                new CurlService()
            )
        );
    }
}
