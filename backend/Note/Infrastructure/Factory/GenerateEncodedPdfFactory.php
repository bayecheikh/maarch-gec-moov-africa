<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Generate Encoded Pdf Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Note\Infrastructure\Factory;

use MaarchCourrier\Core\Domain\Note\Port\GenerateEncodedPdfFactoryInterface;
use MaarchCourrier\Note\Application\GenerateEncodedPdf;
use MaarchCourrier\Note\Infrastructure\Repository\NoteRepository;
use MaarchCourrier\Note\Infrastructure\Service\GeneratePdfNotesService;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;

class GenerateEncodedPdfFactory implements GenerateEncodedPdfFactoryInterface
{
    public static function create(): GenerateEncodedPdf
    {
        return new GenerateEncodedPdf(
            new NoteRepository(new UserRepository()),
            new GeneratePdfNotesService()
        );
    }
}
