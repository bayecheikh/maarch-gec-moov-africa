<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Curl Error Interface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Port;

interface CurlErrorInterface
{
    public function getCode(): int;
    public function setCode(int $code): self;
    public function getMessage(): string;
    public function setMessage(string $message): self;
}
