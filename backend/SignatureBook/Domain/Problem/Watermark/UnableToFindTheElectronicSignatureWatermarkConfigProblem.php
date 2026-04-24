<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Could Not Find SignatureBook Watermark Config Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem\Watermark;

use MaarchCourrier\Core\Domain\Problem\Problem;

class UnableToFindTheElectronicSignatureWatermarkConfigProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _UNABLE_TO_FIND_THE_ELECTRONIC_SIGNATURE_WATERMARK_CONFIG,
            404,
            lang: 'unableToFindTheElectronicSignatureWatermarkConfig'
        );
    }
}
