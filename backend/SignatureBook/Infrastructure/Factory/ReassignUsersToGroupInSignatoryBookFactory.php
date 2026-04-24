<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Reassign Users To Group In Signatory Book Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use MaarchCourrier\SignatureBook\Application\Group\ReassignUsersToGroupInSignatoryBook;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurUserService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;

class ReassignUsersToGroupInSignatoryBookFactory
{
    public function create(): ReassignUsersToGroupInSignatoryBook
    {
        $signatureBookUser = new MaarchParapheurUserService();
        $signatureBookConfigLoader = new SignatureServiceJsonConfigLoader();

        return new ReassignUsersToGroupInSignatoryBook(
            $signatureBookUser,
            $signatureBookConfigLoader
        );
    }
}
