<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Delete User To A Group In Signatory Book Factory
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use MaarchCourrier\SignatureBook\Application\User\DeleteUserToAGroupInSignatoryBook;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurUserService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;

class DeleteUserToAGroupInSignatoryBookFactory
{
    public function create(): DeleteUserToAGroupInSignatoryBook
    {
        $signatureBookUser = new MaarchParapheurUserService();
        $signatureBookConfigLoader = new SignatureServiceJsonConfigLoader();

        return new DeleteUserToAGroupInSignatoryBook(
            $signatureBookUser,
            $signatureBookConfigLoader
        );
    }
}
