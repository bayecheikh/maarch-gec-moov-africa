<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Database Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Port;

interface DatabaseServiceInterface
{
    public function beginTransaction(): bool;
    public function commitTransaction(): bool;
    public function rollbackTransaction(): bool;
}
