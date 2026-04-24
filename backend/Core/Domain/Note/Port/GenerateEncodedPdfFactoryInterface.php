<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Generate Encoded Pdf Factory Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Note\Port;

use MaarchCourrier\Note\Application\GenerateEncodedPdf;

interface GenerateEncodedPdfFactoryInterface
{
    public static function create(): GenerateEncodedPdf;
}
