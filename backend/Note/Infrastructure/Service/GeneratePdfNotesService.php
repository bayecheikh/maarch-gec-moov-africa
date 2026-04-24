<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Generate Pdf Notes Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Note\Infrastructure\Service;

use MaarchCourrier\Core\Domain\Note\Port\NoteInterface;
use MaarchCourrier\Note\Domain\Port\GeneratePdfNotesServiceInterface;
use setasign\Fpdi\Tcpdf\Fpdi;
use SrcCore\models\CoreConfigModel;

class GeneratePdfNotesService implements GeneratePdfNotesServiceInterface
{
    /**
     * @param NoteInterface[] $notes
     *
     * @return string Pdf file content string
     */
    public function getPdfFileContent(array $notes): string
    {
        $libPath = CoreConfigModel::getFpdiPdfParserLibrary();
        if (file_exists($libPath)) {
            require_once($libPath);
        }
        $pdf = new Fpdi('P', 'pt');
        $pdf->setPrintHeader(false);
        $pdf->AddPage();

        foreach ($notes as $note) {
            $date = $note->getCreationDate();
            $date = $date->format('d-m-Y H:i');

            $pdf->Cell(0, 20, "{$note->getCreator()->getFullName()} : $date", 1, 2, 'C');
            $pdf->MultiCell(0, 20, $note->getNoteText(), 1, 'L');
            $pdf->SetY($pdf->GetY() + 40);
        }
        return $pdf->Output('', 'S');
    }
}
