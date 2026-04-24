<?php

namespace MaarchCourrier\Core\Domain;

use MaarchCourrier\Core\Domain\Port\CurlErrorInterface;

class CurlError implements CurlErrorInterface
{
    private int $code;
    private string $message;

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): CurlErrorInterface
    {
        $this->code = $code;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): CurlErrorInterface
    {
        $this->message = $message;
        return $this;
    }
}
