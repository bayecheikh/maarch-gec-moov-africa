<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve SignatureBook Watermark Configuration Factory Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\SignatureBook\Port;

use MaarchCourrier\SignatureBook\Application\Watermark\RetrieveSignatureBookWatermarkConfiguration;

interface RetrieveSignatureBookWatermarkConfigFactoryInterface
{
    public function create(): RetrieveSignatureBookWatermarkConfiguration;
}
