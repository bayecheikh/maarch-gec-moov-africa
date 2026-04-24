<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Token Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain;

use stdClass;

interface TokenServiceInterface
{
    public function generate(array $payload): string;
    public function decode(string $token): ?stdClass;
}
