<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Api Could Not Download Documents Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagApiCouldNotDownloadDocumentsProblem extends Problem
{
    public function __construct(string $detail, int $status)
    {
        parent::__construct(
            _GOODFLAG_API_COULD_NOT_DOWNLOAD_DOCUMENTS_ . " : $detail",
            $status,
            [
                'error' => $detail
            ],
            'goodflagApiCouldNotDownloadDocuments'
        );
    }
}
