<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureBook Curl Request Error Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class SignatureBookCurlRequestErrorProblem extends Problem
{
    public function __construct(int $httpCode, string|array $content)
    {
        $info = "";

        if (!empty($content)) {
            if (is_string($content)) {
                $info = " : $content";
            } elseif (!empty($content['errors'] ?? null)) {
                $info = " : {$content['errors']}";
            } elseif (!empty($content['message'] ?? null) && !empty($content['exception'] ?? null)) {
                $info = " : {$content['message']}";
                $content['context']['exception'] = $content['exception'];
            }
        }

        $context = $content['context'] ?? [];

        parent::__construct(
            _SIGNATORY_BOOK_REQUEST_FAILED . $info,
            $httpCode,
            $context
        );
    }
}
