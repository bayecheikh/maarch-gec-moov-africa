<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Contact\Port;

interface ContactRepositoryInterface
{
    public function getById(int $id): ?ContactInterface;
}
