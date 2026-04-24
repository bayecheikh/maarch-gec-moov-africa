<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Template ID Not Found Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GoodflagTemplateIdNotFound extends Problem
{
    public function __construct(string $templateId)
    {
        parent::__construct(
            _GOODFLAG_TEMPLATE_ID_NOT_FOUND_ . " : $templateId",
            400,
            [
                'templateId' => $templateId
            ],
            lang: 'goodflagTemplateIdNotFound'
        );
    }
}
