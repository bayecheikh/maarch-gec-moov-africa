<?php

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MailevaShippingNotFoundProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _MAILEVA_SHIPPING_NOT_FOUND_,
            400,
            lang: 'missingShippingRecord'
        );
    }
}
