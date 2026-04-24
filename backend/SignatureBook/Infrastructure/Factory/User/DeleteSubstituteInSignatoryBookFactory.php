<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Delete Substitute In Signatory Book Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory\User;

use MaarchCourrier\Core\Domain\SignatureBook\Port\DeleteSubstituteInSignatoryBookFactoryInterface;
use MaarchCourrier\SignatureBook\Application\User\DeleteSubstituteInSignatoryBook;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurUserService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;

class DeleteSubstituteInSignatoryBookFactory implements DeleteSubstituteInSignatoryBookFactoryInterface
{
    public function create(): DeleteSubstituteInSignatoryBook
    {
        return new DeleteSubstituteInSignatoryBook(
            new MaarchParapheurUserService(),
            new SignatureServiceJsonConfigLoader()
        );
    }
}
