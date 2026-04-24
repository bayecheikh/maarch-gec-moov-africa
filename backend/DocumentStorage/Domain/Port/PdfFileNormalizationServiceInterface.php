<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 * /

/**
 * @brief   pdfFileNormalization Service Interface
 * @author  dev <dev@maarch.org>
 * @ingroup core
 */

namespace MaarchCourrier\DocumentStorage\Domain\Port;

interface PdfFileNormalizationServiceInterface
{
    public function normalize(string $pdfBytes): string;
}
