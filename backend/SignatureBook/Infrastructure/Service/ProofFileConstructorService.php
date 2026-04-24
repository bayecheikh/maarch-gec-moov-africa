<?php

namespace MaarchCourrier\SignatureBook\Infrastructure\Service;

use DOMDocument;
use DOMException;
use DOMNode;
use Exception;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\Core\Infrastructure\LangService;
use MaarchCourrier\SignatureBook\Domain\Port\ProofFileConstructorServiceInterface;
use ZipArchive;
use setasign\Fpdi\Tcpdf\Fpdi;

class ProofFileConstructorService implements ProofFileConstructorServiceInterface
{
    /**
     * @param array $docsToZip
     * @return array|string[]
     */
    public function createZip(array $docsToZip): array
    {
        $environnement = new Environment();

        $zip = new ZipArchive();
        $zipFilename = $environnement->getTmpDir() . 'archivedProof' . '_' . rand() . '.zip';

        if ($zip->open($zipFilename, ZipArchive::CREATE) === true) {
            foreach ($docsToZip as $doc) {
                if (file_exists($doc['path']) && filesize($doc['path']) > 0) {
                    $zip->addFile($doc['path'], $doc['filename']);
                }
            }
            $zip->close();
            $fileContent = file_get_contents($zipFilename);
            unlink($zipFilename);
            return ['fileContent' => $fileContent];
        } else {
            return ['errors' => 'Cannot create archive'];
        }
    }

    /**
     * @param array $array
     * @param string $rootElement
     * @return string
     * @throws DOMException
     */
    public function makeXmlFromArray(array $array, string $rootElement = "root"): string
    {
        $environnement = new Environment();

        $dom = new DOMDocument('1.0', 'UTF-8');

        $dom->preserveWhiteSpace = false; // Supprimer les espaces blancs superflus
        $dom->formatOutput = true; // Formater la sortie

        $root = $dom->appendChild($dom->createElement($rootElement));

        $this->arrayToXml($array, $root);

        $filename = $environnement->getTmpDir() . DIRECTORY_SEPARATOR . 'maarchProofFile.xml';
        $dom->save($filename);

        return $filename;
    }

    /**
     * @param array $data
     * @param DOMNode $xmlData
     * @return void
     * @throws DOMException
     */
    private function arrayToXml(array $data, DOMNode $xmlData): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'step';
            }
            if (is_array($value)) {
                $subnode = $xmlData->appendChild($xmlData->ownerDocument->createElement($key));
                $this->arrayToXml($value, $subnode);
            } else {
                $xmlData->appendChild($xmlData->ownerDocument->createElement($key, htmlspecialchars($value)));
            }
        }
    }

    /**
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function makePdfFromArray(array $data): string
    {
        $environnement = new Environment();

        $imagesDirectory = 'dist/assets/';

        /* En-tête du fichier PDF */
        $pdf = new Fpdi('P', 'pt');
        $pdf->setPrintHeader(false);

        $pdf->AddPage();
        $dimensions = $pdf->getPageDimensions();

        $widthNoMargins = $dimensions['w'] - $dimensions['rm'] - $dimensions['lm'];

        $pdf->ImageSVG($imagesDirectory . 'logo.svg', $dimensions['lm'], $dimensions['tm'], 110);
        $pdf->ImageSVG($imagesDirectory . 'maarch_box.svg', $widthNoMargins - 110, $dimensions['tm'], 110);

        $pdf->SetY(120);

        $this->addGeneralDocumentInfo($pdf, $data, $dimensions);

        $this->addWorkflowInformations($pdf, $data, $dimensions);


        $proofFileContent = $pdf->Output('', 'S');
        $filename = $environnement->getTmpDir() . DIRECTORY_SEPARATOR . 'proofFile' . "_" . rand() . '.pdf';
        file_put_contents($filename, $proofFileContent);
        return $filename;
    }

    /**
     * @param Fpdi $pdf
     * @param array $data
     * @param array $dimensions
     * @return void
     * @throws Exception
     */
    private function addGeneralDocumentInfo(Fpdi $pdf, array $data, array $dimensions): void
    {
        $langService = new LangService(new Environment());
        $lang = $langService->getLanguage()['lang'];

        $imagesDirectory = 'dist/assets/';

        $widthNoMargins = $dimensions['w'] - $dimensions['rm'] - $dimensions['lm'];
        $widthTitleCell = $widthNoMargins * 0.25;
        $widthContentCell = $widthNoMargins * 0.75;

        $pdf->SetFont('', 'B', 14);
        $pdf->setTextColor(19, 95, 127);
        $pdf->MultiCell(
            20,
            15,
            $pdf->Image($imagesDirectory . 'proofFile_document.png', $pdf->GetX(), $pdf->GetY(), 15, 15),
            0,
            'L',
            false,
            0,
            $dimensions['lm'] + 20,
            null,
            true,
            0,
            true
        );

        $pdf->MultiCell(
            $widthNoMargins - 20,
            15,
            ucfirst((!$data['resource']['isAttachment']) ? $lang['mainDocument'] : $lang['attachment']),
            0,
            'L',
            false,
            2,
            $dimensions['lm'] + 20,
            null,
            true,
            0,
            true
        );
        $pdf->setTextColor(0, 0, 0);

        //Objet
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(
            $widthTitleCell,
            15,
            $lang['subject'],
            0,
            'L',
            false,
            0,
            $dimensions['lm'],
            null,
            true,
            0,
            true
        );
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(
            $widthContentCell,
            15,
            "{$data['resource']['title']}",
            0,
            'L',
            false,
            2,
            null,
            null,
            true,
            0,
            true
        );

        //Rédacteur
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(
            $widthTitleCell,
            15,
            $lang['createdBy'],
            0,
            'L',
            false,
            0,
            $dimensions['lm'],
            null,
            true,
            0,
            true
        );
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(
            $widthContentCell,
            15,
            "{$data['resource']['creator']}",
            0,
            'L',
            false,
            2,
            null,
            null,
            true,
            0,
            true
        );

        //Date de création
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(
            $widthTitleCell,
            15,
            $lang['createdOn'],
            0,
            'L',
            false,
            0,
            $dimensions['lm'],
            null,
            true,
            0,
            true
        );
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(
            $widthContentCell,
            15,
            "{$data['resource']['creationDate']}",
            0,
            'L',
            false,
            2,
            null,
            null,
            true,
            0,
            true
        );

        //Chrono
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(
            $widthTitleCell,
            15,
            $lang['reference'],
            0,
            'L',
            false,
            0,
            $dimensions['lm'],
            null,
            true,
            0,
            true
        );
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(
            $widthContentCell,
            15,
            "{$data['resource']['chrono']}",
            0,
            'L',
            false,
            2,
            null,
            null,
            true,
            0,
            true
        );

        //Nom du fichier
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(
            $widthTitleCell,
            15,
            $lang['filename'],
            0,
            'L',
            false,
            0,
            $dimensions['lm'],
            null,
            true,
            0,
            true
        );
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(
            $widthContentCell,
            15,
            "{$data['resource']['filename']}",
            0,
            'L',
            false,
            2,
            null,
            null,
            true,
            0,
            true
        );

        //Empreinte
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(
            $widthTitleCell,
            15,
            $lang['fingerprint'],
            0,
            'L',
            false,
            0,
            $dimensions['lm'],
            null,
            true,
            0,
            true
        );
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell(
            $widthContentCell,
            15,
            "{$data['resource']['fingerprint']}",
            0,
            'L',
            false,
            2,
            null,
            null,
            true,
            0,
            true
        );

        $pdf->SetY($pdf->GetY() + 10);
    }

    /**
     * @param Fpdi $pdf
     * @param array $data
     * @param array $dimensions
     * @return void
     * @throws Exception
     */
    private function addWorkflowInformations(Fpdi $pdf, array $data, array $dimensions): void
    {
        $imagesDirectory = 'dist/assets/';

        $langService = new LangService(new Environment());
        $lang = $langService->getLanguage()['lang'];

        $widthNoMargins = $dimensions['w'] - $dimensions['rm'] - $dimensions['lm'];
        $widthTitleCell = $widthNoMargins * 0.25;
        $widthContentCell = $widthNoMargins * 0.75;
        $bottomHeight = $dimensions['h'] - $dimensions['bm'];

        foreach ($data['workflow'] as $itemHistory) {
            if (($pdf->GetY() + 80) > $bottomHeight) {
                $pdf->AddPage();
            }

            $pdf->SetDrawColor(19, 95, 127);
            $pdf->setLineStyle(['width' => 1, 'dash' => 2]);
            $pdf->Line($dimensions['lm'] + 20, $pdf->GetY(), $widthNoMargins - 20, $pdf->GetY());

            $pdf->SetY($pdf->GetY() + 5);

            $pdf->SetFont('', 'B', 14);
            $pdf->setTextColor(19, 95, 127);
            if ($itemHistory['role'] === 'visa') {
                $pdf->MultiCell(
                    $widthNoMargins,
                    15,
                    ucfirst($lang['visaUser']),
                    0,
                    'L',
                    false,
                    2,
                    $dimensions['lm'],
                    null,
                    true,
                    0,
                    true
                );
            } elseif ($itemHistory['role'] === 'sign') {
                $pdf->MultiCell(
                    $widthNoMargins,
                    15,
                    ucfirst($lang['signUser']),
                    0,
                    'L',
                    false,
                    2,
                    $dimensions['lm'],
                    null,
                    true,
                    0,
                    true
                );
            }

            $pdf->SetY($pdf->GetY() + 5);
            $pdf->setTextColor(0, 0, 0);

            //Process date
            $pdf->SetFont('', 'B', 9);
            $pdf->MultiCell(
                $widthTitleCell,
                15,
                $lang["date"],
                0,
                'L',
                false,
                0,
                $dimensions['lm'] + 20,
                null,
                true,
                0,
                true
            );
            $pdf->SetFont('', '', 9);
            $pdf->MultiCell(
                $widthContentCell - 20,
                15,
                "{$itemHistory['processDate']}",
                0,
                'L',
                false,
                2,
                null,
                null,
                true,
                0,
                true
            );

            if (!empty($itemHistory['message'])) {
                $pdf->SetFont('', 'B', 9);
                $pdf->MultiCell(
                    $widthTitleCell,
                    15,
                    $lang["action"],
                    0,
                    'L',
                    false,
                    0,
                    $dimensions['lm'] + 20,
                    null,
                    true,
                    0,
                    true
                );
                $pdf->SetFont('', '', 9);
                $pdf->MultiCell(
                    $widthContentCell - 20,
                    15,
                    "{$itemHistory['message']}",
                    0,
                    'L',
                    false,
                    2,
                    null,
                    null,
                    true,
                    0,
                    true
                );
            }

            $pdf->SetY($pdf->GetY() + 5);

            $pdf->setTextColor(19, 95, 127);
            $pdf->SetFont('', 'B', 12);
            $iconUser = $imagesDirectory . 'proofFile_user.png';
            $pdf->Image($iconUser, $dimensions['lm'] + 25, $pdf->GetY(), 12, 12);
            $pdf->MultiCell(
                $widthNoMargins - 50,
                15,
                ucfirst($lang['user']),
                0,
                'L',
                false,
                2,
                $dimensions['lm'] + 50,
                null,
                true,
                0,
                true
            );
            $pdf->setTextColor(0, 0, 0);

            foreach ($itemHistory['user'] as $keyUser => $infoUser) {
                $pdf->SetFont('', 'B', 9);
                $pdf->MultiCell(
                    $widthTitleCell,
                    15,
                    $lang[$keyUser] ?? $keyUser,
                    0,
                    'L',
                    false,
                    0,
                    $dimensions['lm'] + 20,
                    null,
                    true,
                    0,
                    true
                );
                $pdf->SetFont('', '', 9);
                $pdf->MultiCell(
                    $widthContentCell - 20,
                    15,
                    "{$infoUser}",
                    0,
                    'L',
                    false,
                    2,
                    null,
                    null,
                    true,
                    0,
                    true
                );
            }

            if ($itemHistory['role'] === 'sign') {
                $pdf->SetY($pdf->GetY() + 5);

                //signature
                $pdf->setTextColor(19, 95, 127);
                $pdf->SetFont('', 'B', 12);
                $iconSign = $imagesDirectory . 'proofFile_pencil.png';
                $pdf->Image($iconSign, $dimensions['lm'] + 25, $pdf->GetY(), 12, 12);

                $pdf->MultiCell(
                    $widthNoMargins - 50,
                    15,
                    ucfirst($lang['signature']),
                    0,
                    'L',
                    false,
                    2,
                    $dimensions['lm'] + 50,
                    null,
                    true,
                    0,
                    true
                );
                $pdf->setTextColor(0, 0, 0);

                $pdf->SetFont('', 'B', 9);
                $pdf->MultiCell(
                    $widthTitleCell,
                    15,
                    $lang["type"],
                    0,
                    'L',
                    false,
                    0,
                    $dimensions['lm'] + 20,
                    null,
                    true,
                    0,
                    true
                );
                $pdf->SetFont('', '', 9);
                $pdf->MultiCell(
                    $widthContentCell - 20,
                    15,
                    "{$lang[$itemHistory['signatureMode']]}",
                    0,
                    'L',
                    false,
                    2,
                    null,
                    null,
                    true,
                    0,
                    true
                );
            }

            if (isset($itemHistory['document'])) {
                $pdf->SetY($pdf->GetY() + 5);

                $pdf->setTextColor(19, 95, 127);
                $pdf->SetFont('', 'B', 12);
                $pdf->Image($imagesDirectory . 'proofFile_document.png', $dimensions['lm'] + 25, $pdf->GetY(), 12, 12);
                $pdf->MultiCell(
                    $widthNoMargins - 50,
                    15,
                    ucfirst("Document"),
                    0,
                    'L',
                    false,
                    2,
                    $dimensions['lm'] + 50,
                    null,
                    true,
                    0,
                    true
                );
                $pdf->setTextColor(0, 0, 0);

                $pdf->SetFont('', 'B', 9);
                $pdf->MultiCell(
                    $widthTitleCell,
                    15,
                    $lang["filename"],
                    0,
                    'L',
                    false,
                    0,
                    $dimensions['lm'] + 20,
                    null,
                    true,
                    0,
                    true
                );
                $pdf->SetFont('', '', 9);
                $pdf->MultiCell(
                    $widthContentCell - 20,
                    15,
                    "{$itemHistory['document']['filename']}",
                    0,
                    'L',
                    false,
                    2,
                    null,
                    null,
                    true,
                    0,
                    true
                );

                $pdf->SetFont('', 'B', 9);
                $pdf->MultiCell(
                    $widthTitleCell,
                    15,
                    $lang["fingerprint"],
                    0,
                    'L',
                    false,
                    0,
                    $dimensions['lm'] + 20,
                    null,
                    true,
                    0,
                    true
                );
                $pdf->SetFont('', '', 9);
                $pdf->MultiCell(
                    $widthContentCell - 20,
                    15,
                    "{$itemHistory['document']['fingerprint']}",
                    0,
                    'L',
                    false,
                    2,
                    null,
                    null,
                    true,
                    0,
                    true
                );
            }

            if (isset($itemHistory['document']['certificate'])) {
                $pdf->SetY($pdf->GetY() + 5);

                $pdf->setTextColor(19, 95, 127);
                $pdf->SetFont('', 'B', 12);
                $iconCertif = $imagesDirectory . 'proofFile_ribbon.png';
                $pdf->Image($iconCertif, $dimensions['lm'] + 25, $pdf->GetY(), 12, 12);

                $pdf->MultiCell(
                    $widthNoMargins - 50,
                    15,
                    ucfirst("certificate"),
                    0,
                    'L',
                    false,
                    2,
                    $dimensions['lm'] + 50,
                    null,
                    true,
                    0,
                    true
                );
                $pdf->setTextColor(0, 0, 0);

                $pdf->SetFont('', '', 9);
                $pdf->MultiCell(
                    $widthNoMargins,
                    15,
                    "{$itemHistory['document']['certificate']}",
                    0,
                    'L',
                    false,
                    2,
                    $dimensions['lm'] + 40,
                    null,
                    true,
                    0,
                    true
                );
            }

            $pdf->SetY($pdf->GetY() + 5);
        }
    }
}
