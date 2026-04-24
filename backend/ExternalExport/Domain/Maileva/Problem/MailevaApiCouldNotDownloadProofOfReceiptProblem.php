<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Api Could Not Download Proof of Receipt Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MailevaApiCouldNotDownloadProofOfReceiptProblem extends Problem
{
    public function __construct(string $detail, int $status)
    {
        parent::__construct(
            _MAILEVA_API_COULD_NOT_DOWNLOAD_PROOF_OF_RECEIPT_ . " : $detail",
            $status,
            [
                'error' => $detail
            ],
            'mailevaApiCouldNotDownloadProofOfReceipt'
        );
    }
}
