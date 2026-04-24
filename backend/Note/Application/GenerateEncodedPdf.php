<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Generate Encoded Pdf class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Note\Application;

use MaarchCourrier\Core\Domain\Note\Problem\CouldNotFindNotesProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterArrayCanNotBeEmptyProblem;
use MaarchCourrier\Note\Domain\Port\GeneratePdfNotesServiceInterface;
use MaarchCourrier\Note\Domain\Port\NoteRepositoryInterface;

class GenerateEncodedPdf
{
    public function __construct(
        private readonly NoteRepositoryInterface $noteRepository,
        private readonly GeneratePdfNotesServiceInterface $generatePdfNotesService
    ) {
    }

    /**
     * @param int[] $noteIds
     *
     * @return string Encoded string
     * @throws ParameterArrayCanNotBeEmptyProblem
     * @throws CouldNotFindNotesProblem
     */
    public function getByIds(array $noteIds): string
    {
        if (empty($noteIds)) {
            throw new ParameterArrayCanNotBeEmptyProblem('noteIds');
        }

        $notes = $this->noteRepository->getNotesByIds($noteIds);
        if (empty($notes)) {
            throw new CouldNotFindNotesProblem($noteIds);
        }

        return $this->generatePdfNotesService->getPdfFileContent($notes);
    }
}
