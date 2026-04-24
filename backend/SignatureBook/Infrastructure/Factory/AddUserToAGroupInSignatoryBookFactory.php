<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Add User To A Group In Signatory Book Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use MaarchCourrier\SignatureBook\Application\User\AddUserToAGroupInSignatoryBook;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurUserService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;

class AddUserToAGroupInSignatoryBookFactory
{
    /**
     * @return AddUserToAGroupInSignatoryBook
     */
    public function create(): AddUserToAGroupInSignatoryBook
    {
        $signatureBookUser = new MaarchParapheurUserService();
        $signatureBookConfigLoader = new SignatureServiceJsonConfigLoader();

        return new AddUserToAGroupInSignatoryBook(
            $signatureBookUser,
            $signatureBookConfigLoader
        );
    }
}
