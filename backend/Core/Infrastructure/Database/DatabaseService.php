<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Database Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Infrastructure\Database;

use Exception;
use MaarchCourrier\Core\Domain\Port\DatabaseServiceInterface;
use SrcCore\models\DatabaseModel;

class DatabaseService implements DatabaseServiceInterface
{
    /**
     * @throws Exception
     */
    public function beginTransaction(): bool
    {
        return DatabaseModel::beginTransaction();
    }

    /**
     * @throws Exception
     */
    public function commitTransaction(): bool
    {
        return DatabaseModel::commitTransaction();
    }

    /**
     * @throws Exception
     */
    public function rollbackTransaction(): bool
    {
        return DatabaseModel::rollbackTransaction();
    }
}
