<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 * /
 *
 * /**
 * @brief   pdfFileNormalization Service
 * @author  dev <dev@maarch.org>
 * @ingroup core
 */

namespace MaarchCourrier\DocumentStorage\Infrastructure\Service;

use MaarchCourrier\DocumentStorage\Domain\Port\PdfFileNormalizationServiceInterface;
use SetaPDF_Core_Document;
use SetaPDF_Core_Document_Page_Contents;
use SetaPDF_Core_Reader_String;
use SetaPDF_Core_Writer_String;
use SetaPDF_Core_Type_Array;
use SetaPDF_Core_Type_IndirectObject;
use SetaPDF_Core_Type_IndirectReference;
use SetaPDF_Core_Type_Stream;
use SetaPDF_FormFiller;
use SrcCore\models\CoreConfigModel;
use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Throwable;

class PdfFileNormalizationService implements PdfFileNormalizationServiceInterface
{
    /**
     * Normalizes a PDF string using the FPDI library. It processes the PDF to correct its
     * page dimensions and adds a slight white margin to prevent visual imperfections.
     * If the FPDI library is unavailable or an error occurs, the original PDF string is returned.
     *
     * @param string $pdfBytes The binary content of the PDF as a string.
     * @param array $options Optional configuration settings, such as:
     *                       - 'box': (string) The PDF bounding box to use ('/MediaBox' or '/CropBox').
     * @return string The normalized PDF as a binary string, or the original PDF content in case of errors.
     */
    private function normalizePdfWithFpdi(string $pdfBytes, array $options = []): string
    {
        $libPath = CoreConfigModel::getFpdiPdfParserLibrary();
        if (file_exists($libPath)) {
            require_once($libPath);

            try {
                // Options
                $box = $options['box'] ?? '/MediaBox'; // '/MediaBox' (par défaut) ou '/CropBox'

                // Source depuis une string
                $src = StreamReader::createByString($pdfBytes);

                // FPDI en points
                $pdf = new Fpdi('P', 'pt');
                $pdf->SetMargins(0, 0, 0);
                $pdf->SetAutoPageBreak(false);
                $pageCount = $pdf->setSourceFile($src); // IMPORTANT: un seul setSourceFile()

                for ($i = 1; $i <= $pageCount; $i++) {
                    // Import de la page comme template (rotation gérée par FPDI)
                    $tplId = $pdf->importPage($i, $box);
                    $size = $pdf->getTemplateSize($tplId);

                    // Orientation physique de la page de sortie (pas de /Rotate)
                    $orientation = ($size['width'] >= $size['height']) ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);

                    // Fond blanc très légèrement débordant pour éviter la "hairline"
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->Rect(-1, -1, $size['width'] + 2, $size['height'] + 2, 'F');

                    // Pose du template plein cadre
                    $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height'], false);

                    // (Optionnel – alternative au fond blanc: clipping très strict)
                    // $pdf->_out('q');
                    // $pdf->_out(sprintf('0 0 %.2F %.2F re W n', $size['width'], $size['height']));
                    // $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height'], false);
                    // $pdf->_out('Q');
                }

                // Retourne le PDF normalisé (string)
                return $pdf->Output("", 'S'); // 'S' = retourne la chaîne binaire
            } catch (Throwable) {
                return $pdfBytes;
            }
        }
        return $pdfBytes;
    }

    /**
     * Détecte si un PDF doit être normalisé (sans mutool).
     * True si :
     *  - au moins une page a /Rotate != 0
     *  - au moins une page a /UserUnit != 1
     *
     * Implémentation robuste :
     *  - 1 appel pdfinfo global (parse "Page rot:" et "UserUnit:")
     *  - si >1 page : 1 appel pdfinfo -f 1 -l N (parse "Page <n> rot:")
     */
    private function pdfHasTransformations(string $pdfBytes, array $options = []): bool
    {
        $pdfinfoPath = $options['pdfinfoPath'] ?? 'pdfinfo';

        // 1) Fichier temporaire
        $tmpDir = rtrim(CoreConfigModel::getTmpPath(), '/') . DIRECTORY_SEPARATOR;
        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            return false;
        }
        $base = 'tmp_probe_' . ($GLOBALS['id'] ?? getmypid()) . '_' . mt_rand();
        $pdfPath = $tmpDir . $base . '.pdf';
        file_put_contents($pdfPath, $pdfBytes);

        // Helper exec: environnement stable
        $run = static function (string $cmd, string $cwd): array {
            $path = getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
            // Forcer locale neutre pour des clés "Page rot", "UserUnit" en anglais
            $prefix = 'LC_ALL=C LANG=C ';
            $full = 'cd ' . escapeshellarg($cwd) . ' && ' . $prefix .
                'PATH=' . escapeshellarg($path) . ' ' . $cmd . ' 2>&1';
            $out = [];
            $code = 0;
            exec($full, $out, $code);
            return [$code, implode("\n", $out)];
        };

        try {
            // ---- Appel global
            [$code, $out] = $run(escapeshellcmd($pdfinfoPath) . ' ' . escapeshellarg($pdfPath), $tmpDir);
            if ($code !== 0) {
                return false;
            }

            // Pages
            $pages = 0;
            if (preg_match('/^Pages:\s+(\d+)/mi', $out, $m)) {
                $pages = (int)$m[1];
            }

            // 1a) Détection /Rotate globale (certaines versions affichent la rotation de la 1re page)
            if (preg_match('/Page rot:\s*([0-9]+)/i', $out, $rm)) {
                $rot = ((int)$rm[1]) % 360;
                if ($rot !== 0) {
                    return true;
                }
            }

            // 1b) Détection UserUnit globale
            // (selon versions, "UserUnit :" peut apparaître dans la sortie globale ou non)
            if (preg_match('/UserUnit:\s*([0-9.]+)/i', $out, $um)) {
                $uu = (float)$um[1];
                if (abs($uu - 1.0) > 1e-6) {
                    return true;
                }
            }

            // Rien vu en global ? Si >1 page, refais un seul pdfinfo sur toutes les pages.
            if ($pages > 1) {
                [$c2, $out2] = $run(
                    sprintf(
                        '%s -f 1 -l %d %s',
                        escapeshellcmd($pdfinfoPath),
                        $pages,
                        escapeshellarg($pdfPath)
                    ),
                    $tmpDir
                );
                if ($c2 === 0) {
                    // Cherche "Page <n> rot: <angle>"
                    if (preg_match('/Page\s+\d+\s+rot:\s*([0-9]+)/i', $out2, $rm2)) {
                        $rot = ((int)$rm2[1]) % 360;
                        if ($rot !== 0) {
                            return true;
                        }
                    }
                    // Cherche un UserUnit non 1 (rarement imprimé par page, mais on tente)
                    if (preg_match('/UserUnit:\s*([0-9.]+)/i', $out2, $um2)) {
                        $uu = (float)$um2[1];
                        if (abs($uu - 1.0) > 1e-6) {
                            return true;
                        }
                    }
                }
            }

            try {
                if ($this->pdfHasEarlyCmTransformationWithSetaPdf($pdfBytes)) {
                    return true;
                }
            } catch (Throwable $ignored) {
                // Si SetaPDF/Core pas disponible ou API différente, on ignore.
                echo $ignored->getMessage();
            }

            // Si on arrive ici : pas de Rotate ni UserUnit anormal détecté
            return false;
        } finally {
            @unlink($pdfPath);
        }
    }

    /**
     * Détecte s'il existe un opérateur "cm" dans le flux d'une page
     * avant toute opération de peinture (texte, forme, image...).
     * → Retourne TRUE s'il y a une transformation précoce.
     */
    private function pdfHasEarlyCmTransformationWithSetaPdf(string $pdfBytes): bool
    {
        $libPath = CoreConfigModel::getSetaPdfFormFillerLibrary();
        if (!file_exists($libPath)) {
            return false;
        }
        require_once($libPath);

        $reader = new SetaPDF_Core_Reader_String($pdfBytes);
        $document = SetaPDF_Core_Document::load($reader);
        $pages = $document->getCatalog()->getPages();
        $count = $pages->count();

        $reCm = '/\bcm\b/';
        $rePaint = '/\b(Tj|TJ|Do|S|s|f\*?|F|B\*?|b\*?|re|m|l|BT|ET)\b/';

        for ($i = 1; $i <= $count; $i++) {
            $page = $pages->getPage($i);
            $contents = $page->getContents();
            $decodedList = [];

            // Cas 1 : flux direct
            if ($contents instanceof SetaPDF_Core_Type_Stream) {
                $decodedList[] = $contents->getStream(true) ?? '';
            } elseif ($contents instanceof SetaPDF_Core_Type_Array) {
                foreach ($contents->getValue() as $el) {
                    $streamObj = $this->resolveToStream($el);
                    if ($streamObj instanceof SetaPDF_Core_Type_Stream) {
                        $decodedList[] = $streamObj->getStream(true) ?? '';
                    }
                }
            } elseif ($contents instanceof SetaPDF_Core_Document_Page_Contents) {
                // getStream() concatène tout en un seul flux logique
                $decodedList[] = $contents->getStream();
            } elseif (method_exists($contents, 'getStreamObject')) {
                $sObj = $contents->getStreamObject();
                if ($sObj instanceof SetaPDF_Core_Type_IndirectObject) {
                    $val = $sObj->getValue();
                    if ($val instanceof SetaPDF_Core_Type_Stream) {
                        $decodedList[] = $val->getStream(true) ?? '';
                    }
                }
            }

            foreach ($decodedList as $decoded) {
                if (!is_string($decoded) || trim($decoded) === '') {
                    continue;
                }

                $cmPos = preg_match($reCm, $decoded, $m1, PREG_OFFSET_CAPTURE) ? $m1[0][1] : null;
                $paintPos = preg_match($rePaint, $decoded, $m2, PREG_OFFSET_CAPTURE) ? $m2[0][1] : null;

                if ($cmPos !== null && ($paintPos === null || $cmPos < $paintPos)) {
                    return true; // Transformation détectée avant dessin
                }
            }
        }

        return false;
    }

    /** Résolution utilitaire vers Stream */
    private function resolveToStream(mixed $el): ?SetaPDF_Core_Type_Stream
    {
        if ($el instanceof SetaPDF_Core_Type_IndirectReference) {
            $el = $el->getValue();
        }
        if ($el instanceof SetaPDF_Core_Type_IndirectObject) {
            $el = $el->getValue();
        }
        return ($el instanceof SetaPDF_Core_Type_Stream) ? $el : null;
    }

    /**
     * Extracts annotations from a PDF document provided as a binary string.
     * It processes each page of the PDF and retrieves any annotations present,
     * organizing them by page number.
     *
     * @param string $pdfBytes The binary content of the PDF as a string.
     * @return array An associative array where keys are page numbers (int) and
     *               values are arrays of annotations for the corresponding page.
     *               If no annotations are found or the required library is missing,
     *               an empty array is returned.
     */
    private function extractAnnotations(string $pdfBytes): array
    {
        $libPath = CoreConfigModel::getSetaPdfFormFillerLibrary();
        if (!file_exists($libPath)) {
            return [];
        }
        require_once($libPath);

        $reader = new SetaPDF_Core_Reader_String($pdfBytes);
        $document = SetaPDF_Core_Document::load($reader);

        $pages = $document->getCatalog()->getPages();
        $pageCount = $pages->count();

        $annotations = [];

        for ($i = 1; $i <= $pageCount; $i++) {
            $page = $pages->getPage($i);
            $annotsObj = $page->getAnnotations();

            if ($annotsObj === null) {
                continue;
            }

            $annots = $annotsObj->getAll(); // ✅ la bonne API en SetaPDF2

            if (empty($annots)) {
                continue;
            }

            foreach ($annots as $annot) {
                $annotations[$i][] = $annot;
            }
        }

        return $annotations;
    }

    /**
     * Reapplies annotations to a normalized PDF document using the SetaPDF library.
     * This method iterates through the given annotations and reinserts them into their
     * respective pages in the normalized PDF. If the SetaPDF library is unavailable,
     * the original normalized PDF is returned without modifications.
     *
     * @param string $normalizedPdf The binary content of the normalized PDF as a string.
     * @param array $annotations An associative array of annotations to reapply, where:
     *                           - The key is the page number (int), starting from 1.
     *                           - The value is an array of annotation objects to add to the page.
     * @return string The PDF content with reapplied annotations as a binary string.
     */
    private function reapplyAnnotations(string $normalizedPdf, array $annotations): string
    {
        $libPath = CoreConfigModel::getSetaPdfFormFillerLibrary();
        if (!file_exists($libPath)) {
            return $normalizedPdf;
        }
        require_once($libPath);

        $reader = new SetaPDF_Core_Reader_String($normalizedPdf);
        $writer = new SetaPDF_Core_Writer_String();

        $doc = SetaPDF_Core_Document::load($reader, $writer);
        $pages = $doc->getCatalog()->getPages();

        foreach ($annotations as $pageNum => $annots) {
            if ($pageNum > $pages->count()) {
                continue;
            }

            $page = $pages->getPage($pageNum);
            $pageAnnots = $page->getAnnotations(true); // crée /Annots si absent

            foreach ($annots as $annotRef) {
                $pageAnnots->add($annotRef);
            }
        }

        $doc->save()->finish();
        return $writer->getBuffer();
    }


    private function pdfHasForm(string $pdfBytes): bool
    {
        $libPath = CoreConfigModel::getSetaPdfFormFillerLibrary();
        if (!file_exists($libPath)) {
            return false;
        }
        require_once($libPath);

        $reader = new SetaPDF_Core_Reader_String($pdfBytes);
        $doc = SetaPDF_Core_Document::load($reader);

        $acroForm = $doc->getCatalog()->getAcroForm();

        if ($acroForm === null) {
            return false;
        }

        $fields = $acroForm->getFieldsArray();

        if (!$fields) {
            return false;
        }

        return true;
    }

    private function flattenForm(string $pdfBytes): string
    {
        $libPath = CoreConfigModel::getSetaPdfFormFillerLibrary();
        if (!file_exists($libPath)) {
            return $pdfBytes;
        }
        require_once($libPath);

        $reader = new SetaPDF_Core_Reader_String($pdfBytes);
        $writer = new SetaPDF_Core_Writer_String();
        $doc = SetaPDF_Core_Document::load($reader, $writer);

        $formFiller = new SetaPDF_FormFiller($doc);
        $fields = $formFiller->getFields()->getAll();

        foreach ($fields as $field) {
            $field->flatten();
        }

        $doc->save()->finish();

        return $writer->getBuffer();
    }

    public function normalize(string $pdfBytes): string
    {
        if ($this->pdfHasForm($pdfBytes)) {
            $pdfBytes = $this->flattenForm($pdfBytes);
        }

        if (!$this->pdfHasTransformations($pdfBytes)) {
            return $pdfBytes;
        }

        // 1. Extraire les annotations
        $annotations =  $this->extractAnnotations($pdfBytes);

        // 2. Normalisation FPDI
        $normalized = $this->normalizePdfWithFpdi($pdfBytes);

        // 3. Réinjection annotations
        if (!empty($annotations)) {
            return $this->reapplyAnnotations($normalized, $annotations);
        }

        return $normalized;
    }
}
