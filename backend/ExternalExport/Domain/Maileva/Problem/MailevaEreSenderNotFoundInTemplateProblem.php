<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Ere Sender Not Found In Template Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;
use MaarchCourrier\ExternalExport\Domain\Maileva\MailevaTemplate;

class MailevaEreSenderNotFoundInTemplateProblem extends Problem
{
    public function __construct(MailevaTemplate $template)
    {
        parent::__construct(
            _MAILEVA_ERE_SENDER_NOT_FOUND_IN_TEMPLATE . " : " . $template->getLabel(),
            404,
            [
                'template' => [
                    'id'          => $template->getId(),
                    'label'       => $template->getLabel(),
                    'description' => $template->getDescription(),
                ]
            ],
            'mailevaEreSenderNotFoundInTemplate'
        );
    }
}
