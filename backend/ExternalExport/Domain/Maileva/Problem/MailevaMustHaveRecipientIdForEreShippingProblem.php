<?php

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MailevaMustHaveRecipientIdForEreShippingProblem extends Problem
{
    public function __construct(int $shippingId)
    {
        parent::__construct(
            _MAILEVA_MISSING_RECIPIENT_ID_ERE_SHIPPING,
            400,
            ['shippingId' => $shippingId],
            lang: 'mailevaMissingRecipientIdForEreShipping'
        );
    }
}
