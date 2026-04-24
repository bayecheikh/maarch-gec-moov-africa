<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Main Resource File Factory Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource\Port;

use Resource\Application\RetrieveResource;

interface RetrieveMainResourceFileFactoryInterface
{
    public static function create(): RetrieveResource;
}
