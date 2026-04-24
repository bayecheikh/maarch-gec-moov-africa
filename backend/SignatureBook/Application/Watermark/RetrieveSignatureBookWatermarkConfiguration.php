<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve SignatureBook Watermark Configuration
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Watermark;

use MaarchCourrier\SignatureBook\Domain\Watermark\Port\SignatureBookWatermarkConfigurationServiceInterface;

class RetrieveSignatureBookWatermarkConfiguration
{
    public function __construct(
        private readonly SignatureBookWatermarkConfigurationServiceInterface $electronicWatermarkConfigurationService
    ) {
    }

    public function execute(): array
    {
        $this->electronicWatermarkConfigurationService->loadConfig();
        $config = $this->electronicWatermarkConfigurationService->fetch();

        $config['color'] = $config['fontColor'];
        $config['size'] = $config['fontSize'];
        $config['opacity'] = $config['fontOpacity'];
        $config['posX'] = $config['xPosition'];
        $config['posY'] = $config['yPosition'];

        unset(
            $config['fontColor'],
            $config['fontSize'],
            $config['fontOpacity'],
            $config['xPosition'],
            $config['yPosition']
        );

        return $config;
    }
}
