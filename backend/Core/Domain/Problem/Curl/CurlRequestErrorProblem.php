<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurlRequestErrorProblem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem\Curl;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CurlRequestErrorProblem extends Problem
{
    public function __construct(int $httpCode, string|array $content)
    {
        $info = "";

        if (!empty($content)) {
            if (is_string($content)) {
                $info = " : $content";
            } elseif (!empty($content['errors'] ?? null)) {
                $info = " : {$content['errors']}";
            }
        }

        $context = $content['context'] ?? [];

        parent::__construct(
            _CURL_REQUEST_FAILED . $info,
            $httpCode,
            $context
        );
    }
}
