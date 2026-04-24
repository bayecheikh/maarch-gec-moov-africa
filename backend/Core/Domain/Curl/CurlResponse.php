<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurlResponse class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Curl;

use JsonSerializable;
use SimpleXMLElement;

class CurlResponse implements JsonSerializable
{
    public function __construct(private int $httpCode, private string|array|SimpleXMLElement $contentReturn)
    {
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function setHttpCode(int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }

    public function getContentReturn(): string|array|SimpleXMLElement
    {
        return $this->contentReturn;
    }

    public function setContentReturn(string|array|SimpleXMLElement $contentReturn): void
    {
        $this->contentReturn = $contentReturn;
    }

    public function jsonSerialize(): array
    {
        return [
            'httpCode'      => $this->httpCode,
            'contentReturn' => $this->contentReturn
        ];
    }
}
