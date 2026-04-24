<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MercureServiceInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Mercure\Domain\Port;

interface MercureServiceInterface
{
    public function isValidSetup(): bool|array;
    public function isEnabled(?int $modelId): bool;
    public function processLad(string $encodedResource, string $format): array;
}
