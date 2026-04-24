<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief DocServer Type Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain\Port;

use MaarchCourrier\DocumentStorage\Domain\DocServerType;

interface DocServerTypeRepositoryInterface
{
    public function getById(string $id): ?DocServerType;
}
