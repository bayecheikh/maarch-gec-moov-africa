<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Get Users From Group Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory\Group;

use MaarchCourrier\SignatureBook\Application\Group\GetUsersFromGroupInSignatoryBook;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurGroupService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;

class GetUsersFromGroupFactory
{
    /**
     * @return GetUsersFromGroupInSignatoryBook
     */
    public function create(): GetUsersFromGroupInSignatoryBook
    {
        return new GetUsersFromGroupInSignatoryBook(
            new MaarchParapheurGroupService(),
            new SignatureServiceJsonConfigLoader(),
        );
    }
}
