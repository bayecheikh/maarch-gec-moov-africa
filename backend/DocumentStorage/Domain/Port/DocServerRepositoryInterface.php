<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief DocServer Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain\Port;

use MaarchCourrier\DocumentStorage\Domain\DocServer;

interface DocServerRepositoryInterface
{
    public function getByStorageZoneId(string $docServerId): ?DocServer;
}
