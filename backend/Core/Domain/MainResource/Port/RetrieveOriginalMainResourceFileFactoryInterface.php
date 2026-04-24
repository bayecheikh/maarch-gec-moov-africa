<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Original Main Resource File Factory Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource\Port;

use Resource\Application\RetrieveOriginalResource;

interface RetrieveOriginalMainResourceFileFactoryInterface
{
    public static function create(): RetrieveOriginalResource;
}
