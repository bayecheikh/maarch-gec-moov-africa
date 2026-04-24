<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Generate Pdf Notes Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Note\Domain\Port;

use MaarchCourrier\Core\Domain\Note\Port\NoteInterface;

interface GeneratePdfNotesServiceInterface
{
    /**
     * @param NoteInterface[] $notes
     *
     * @return string Pdf file content string
     */
    public function getPdfFileContent(array $notes): string;
}
