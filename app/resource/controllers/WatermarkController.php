<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Watermark Controller
 * @author dev@maarch.org
 */

namespace Resource\controllers;

use Attachment\models\AttachmentModel;
use Configuration\models\ConfigurationModel;
use Exception;
use Resource\models\ResModel;
use SetaPDF_Core_Canvas;
use SetaPDF_Core_Document;
use SetaPDF_Core_Document_Page;
use SetaPDF_Core_Reader_File;
use SetaPDF_Core_Writer_String;
use SetaPDF_Core_DataStructure_Color_Rgb;
use SetaPDF_Core_Font;
use SetaPDF_Core_Font_Standard_Courier;
use SetaPDF_Core_Font_Standard_CourierBold;
use SetaPDF_Core_Font_Standard_CourierBoldOblique;
use SetaPDF_Core_Font_Standard_CourierOblique;
use SetaPDF_Core_Font_Standard_Helvetica;
use SetaPDF_Core_Font_Standard_HelveticaBold;
use SetaPDF_Core_Font_Standard_HelveticaBoldOblique;
use SetaPDF_Core_Font_Standard_HelveticaOblique;
use SetaPDF_Core_Font_Standard_Symbol;
use SetaPDF_Core_Font_Standard_TimesBold;
use SetaPDF_Core_Font_Standard_TimesBoldItalic;
use SetaPDF_Core_Font_Standard_TimesItalic;
use SetaPDF_Core_Font_Standard_TimesRoman;
use SetaPDF_Core_Font_Standard_ZapfDingbats;
use SetaPDF_Core_Resource_ExtGState;
use SetaPDF_Core_PageBoundaries;
use SetaPDF_Core_PageFormats;
use setasign\Fpdi\Tcpdf\Fpdi;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;

class WatermarkController
{
    /**
     * @param array $args
     * @return string|null
     * @throws Exception
     */
    public static function watermarkResource(array $args): ?string
    {
        ValidatorModel::notEmpty($args, ['resId', 'fileContent']);
        ValidatorModel::intVal($args, ['resId']);
        ValidatorModel::stringType($args, ['fileContent']);

        $configuration = ConfigurationModel::getByPrivilege([
            'select'    => ['value'],
            'privilege' => 'admin_parameters_watermark'
        ]);
        if (empty($configuration)) {
            return null;
        }

        $watermark = json_decode($configuration['value'], true);
        if ($watermark['enabled'] != 'true') {
            return null;
        } elseif (empty($watermark['text'])) {
            return null;
        }

        $watermarkText = $watermark['text'];
        preg_match_all('/\[(.*?)\]/i', $watermark['text'], $matches);
        foreach ($matches[1] as $value) {
            if ($value == 'date_now') {
                $tmp = date('d-m-Y');
            } elseif ($value == 'hour_now') {
                $tmp = date('H:i');
            } else {
                $resource = ResModel::getById(['select' => [$value], 'resId' => $args['resId']]);
                $tmp = $resource[$value] ?? '';
            }
            $watermarkText = str_replace("[{$value}]", $tmp, $watermarkText);
        }

        $font = $watermark['font'] ?? 'helvetica';
        $originalFontSize = $watermark['size'] ?? 8;
        $fontColor = [$watermark['color'][0], $watermark['color'][1], $watermark['color'][2]];
        $opacity = $watermark['opacity'] ?? 0.5;

        $preProcessWatermarkFile = CoreConfigModel::getTmpPath() . "tmp_file_{$GLOBALS['id']}_" .
            rand() . "_preprocess_watermark.pdf";
        file_put_contents($preProcessWatermarkFile, $args['fileContent']);

        $libPath = CoreConfigModel::getSetaSignFormFillerLibrary();
        if (!empty($libPath)) {
            require_once($libPath);

            try {
                $reader = new SetaPDF_Core_Reader_File($preProcessWatermarkFile);
                $writer = new SetaPDF_Core_Writer_String();
                $document = SetaPDF_Core_Document::load($reader, $writer);

                $pages = $document->getCatalog()->getPages();
                $nbPages = $pages->count();

                for ($i = 1; $i <= $nbPages; $i++) {
                    // Retrieve current page
                    $page = $pages->getPage($i);

                    // 0) Append a new stream at the end of the page content
                    // Ensures that the watermark is drawn ON TOP of existing content
                    $page->getContents()->pushStream(true);

                    // 1) Prepare a clean canvas
                    $canvas = $page->getCanvas();
                    $canvas->saveGraphicState(); // Save current graphics state (colors, CTM, etc.)
                    $canvas->path('n'); // Clear any active clipping path

                    // 2) Get the effective page box (CropBox or MediaBox)
                    $rect = $page->getBoundary(SetaPDF_Core_PageBoundaries::CROP_BOX, false)
                        ?: $page->getBoundary(SetaPDF_Core_PageBoundaries::MEDIA_BOX, false);
                    [$llx,$lly,$urx,$ury] = $rect->toPhp();

                    // 3) Translate origin to the lower-left corner of the box
                    $canvas->addCurrentTransformationMatrix(1, 0, 0, 1, -$llx, -$lly);

                    // 4) Neutralize any residual scaling/rotation left by previous page content
                    self::neutralizeEndCtm($page, $canvas);

                    // Rotation requested by the user configuration
                    $angleUser = (float)($watermark['angle'] ?? 0);

                    // 5) Compute the final position and responsive size for the watermark
                    [$xp, $yp, $angleDraw, $fontSize] = self::computeWatermarkPosition(
                        $page,
                        $originalFontSize,
                        $watermark['posX'],
                        $watermark['posY'],
                        $angleUser
                    );

                    // 6) Convert RGB color (0–255) to normalized [0–1]
                    $rgb = [$fontColor[0] / 255, $fontColor[1] / 255, $fontColor[2] / 255];

                    // 7) Draw the watermark text
                    self::drawWatermark(
                        $document,
                        $canvas,
                        $watermarkText,
                        $font,
                        $fontSize,
                        $rgb,
                        $opacity,
                        $angleDraw,
                        $xp,
                        $yp
                    );

                    // 8) Restore previous graphics state (avoid side effects for next page)
                    $canvas->restoreGraphicState();
                }

                $document->save()->finish();

                $fileContent = $writer->getBuffer();
            } catch (Exception $e) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'resources',
                    'level'     => 'ERROR',
                    'tableName' => 'res_letterbox',
                    'recordId'  => $args['resId'],
                    'eventType' => 'watermark',
                    'eventId'   => $e->getMessage()
                ]);
                $fileContent = null;
            }
        } else {
            $libPath = CoreConfigModel::getFpdiPdfParserLibrary();
            if (file_exists($libPath)) {
                require_once($libPath);
            }

            try {
                $pdf = new Fpdi('P', 'pt');
                $nbPages = $pdf->setSourceFile($preProcessWatermarkFile);
                $pdf->setPrintHeader(false);
                for ($i = 1; $i <= $nbPages; $i++) {
                    $page = $pdf->importPage($i, 'CropBox');
                    $size = $pdf->getTemplateSize($page);
                    $pdf->AddPage($size['orientation'], $size);
                    $pdf->useImportedPage($page);
                    $pdf->SetFont($watermark['font'], '', $watermark['size']);
                    $pdf->SetTextColor($watermark['color'][0], $watermark['color'][1], $watermark['color'][2]);
                    $pdf->SetAlpha($watermark['opacity']);
                    $pdf->StartTransform();
                    $pdf->Rotate($watermark['angle'], $watermark['posX'], $watermark['posY']);
                    $pdf->Text($watermark['posX'], $watermark['posY'], $watermarkText);
                    $pdf->StopTransform();
                }
                $fileContent = $pdf->Output('', 'S');
            } catch (Exception $e) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'resources',
                    'level'     => 'ERROR',
                    'tableName' => 'res_letterbox',
                    'recordId'  => $args['resId'],
                    'eventType' => 'watermark',
                    'eventId'   => $e->getMessage()
                ]);
                $fileContent = null;
            }
        }
        if (!empty($preProcessWatermarkFile) && is_file($preProcessWatermarkFile)) {
            unlink($preProcessWatermarkFile);
        }

        return $fileContent;
    }

    /**
     * @codeCoverageIgnore
     * @param array $args
     * @return string|null
     * @throws Exception
     */
    public static function watermarkAttachment(array $args): ?string
    {
        ValidatorModel::notEmpty($args, ['attachmentId', 'path']);
        ValidatorModel::intVal($args, ['attachmentId']);
        ValidatorModel::stringType($args, ['path']);

        $configuration = ConfigurationModel::getByPrivilege([
            'select'    => ['value'],
            'privilege' => 'admin_parameters_watermark_attachment'
        ]);
        if (empty($configuration)) {
            return null;
        }

        $watermark = json_decode($configuration['value'], true);
        if ($watermark['enabled'] != 'true') {
            return null;
        } elseif (empty($watermark['text'])) {
            return null;
        }

        $watermarkText = $watermark['text'];
        preg_match_all('/\[(.*?)\]/i', $watermark['text'], $matches);
        foreach ($matches[1] as $value) {
            if ($value == 'date_now') {
                $tmp = date('d-m-Y');
            } elseif ($value == 'hour_now') {
                $tmp = date('H:i');
            } else {
                $attachment = AttachmentModel::getById(['select' => [$value], 'id' => $args['attachmentId']]);
                $tmp = $attachment[$value] ?? '';
            }
            $watermarkText = str_replace("[{$value}]", $tmp, $watermarkText);
        }

        $font = $watermark['font'] ?? 'helvetica';
        $originalFontSize = $watermark['size'] ?? 8;
        $fontColor = [$watermark['color'][0], $watermark['color'][1], $watermark['color'][2]];
        $opacity = $watermark['opacity'] ?? 0.5;

        $watermarkFile = CoreConfigModel::getTmpPath() . "tmp_file_{$GLOBALS['id']}_" . rand() . "_watermark.pdf";
        file_put_contents($watermarkFile, file_get_contents($args['path']));

        $libPath = CoreConfigModel::getSetaSignFormFillerLibrary();
        if (!empty($libPath)) {
            require_once($libPath);

            try {
                $reader = new SetaPDF_Core_Reader_File($watermarkFile);
                $writer = new SetaPDF_Core_Writer_String();
                $document = SetaPDF_Core_Document::load($reader, $writer);

                $pages = $document->getCatalog()->getPages();
                $nbPages = $pages->count();

                for ($i = 1; $i <= $nbPages; $i++) {
                    // Retrieve current page
                    $page = $pages->getPage($i);

                    // 0) Append a new stream at the end of the page content
                    // Ensures that the watermark is drawn ON TOP of existing content
                    $page->getContents()->pushStream(true);

                    // 1) Prepare a clean canvas
                    $canvas = $page->getCanvas();
                    $canvas->saveGraphicState(); // Save current graphics state (colors, CTM, etc.)
                    $canvas->path('n'); // Clear any active clipping path

                    // 2) Get the effective page box (CropBox or MediaBox)
                    $rect = $page->getBoundary(SetaPDF_Core_PageBoundaries::CROP_BOX, false)
                        ?: $page->getBoundary(SetaPDF_Core_PageBoundaries::MEDIA_BOX, false);
                    [$llx,$lly,$urx,$ury] = $rect->toPhp();

                    // 3) Translate origin to the lower-left corner of the box
                    $canvas->addCurrentTransformationMatrix(1, 0, 0, 1, -$llx, -$lly);

                    // 4) Neutralize any residual scaling/rotation left by previous page content
                    self::neutralizeEndCtm($page, $canvas);

                    // Rotation requested by the user configuration
                    $angleUser = (float)($watermark['angle'] ?? 0);

                    // 5) Compute the final position and responsive size for the watermark
                    [$xp, $yp, $angleDraw, $fontSize] = self::computeWatermarkPosition(
                        $page,
                        $originalFontSize,
                        $watermark['posX'],
                        $watermark['posY'],
                        $angleUser
                    );

                    // 6) Convert RGB color (0–255) to normalized [0–1]
                    $rgb = [$fontColor[0] / 255, $fontColor[1] / 255, $fontColor[2] / 255];

                    // 7) Draw the watermark text
                    self::drawWatermark(
                        $document,
                        $canvas,
                        $watermarkText,
                        $font,
                        $fontSize,
                        $rgb,
                        $opacity,
                        $angleDraw,
                        $xp,
                        $yp
                    );

                    // 8) Restore previous graphics state (avoid side effects for next page)
                    $canvas->restoreGraphicState();
                }

                $document->save()->finish();

                $fileContent = $writer->getBuffer();
            } catch (Exception $e) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'resources',
                    'level'     => 'ERROR',
                    'tableName' => 'res_letterbox',
                    'recordId'  => $args['resId'],
                    'eventType' => 'watermark',
                    'eventId'   => $e->getMessage()
                ]);
                $fileContent = null;
            }
        } else {
            $libPath = CoreConfigModel::getFpdiPdfParserLibrary();
            if (file_exists($libPath)) {
                require_once($libPath);
            }
            try {
                $pdf = new Fpdi('P', 'pt');
                $nbPages = $pdf->setSourceFile($watermarkFile);
                $pdf->setPrintHeader(false);
                for ($i = 1; $i <= $nbPages; $i++) {
                    $page = $pdf->importPage($i, 'CropBox');
                    $size = $pdf->getTemplateSize($page);
                    $pdf->AddPage($size['orientation'], $size);
                    $pdf->useImportedPage($page);
                    $pdf->SetFont($font, '', $watermark['size']);
                    $pdf->SetTextColor($fontColor[0], $fontColor[1], $fontColor[2]);
                    $pdf->SetAlpha((float)$opacity);
                    $pdf->StartTransform();
                    $pdf->Rotate($watermark['angle'], $watermark['posX'], $watermark['posY']);
                    $pdf->Text($watermark['posX'], $watermark['posY'], $watermarkText);
                    $pdf->StopTransform();
                }
                $fileContent = $pdf->Output('', 'S');
            } catch (Exception $e) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'attachments',
                    'level'     => 'ERROR',
                    'tableName' => 'res_attachments',
                    'recordId'  => $args['attachmentId'],
                    'eventType' => 'watermark',
                    'eventId'   => $e->getMessage()
                ]);
                $fileContent = null;
            }
        }

        return $fileContent;
    }

    /**
     * Compute the final position of a watermark on a PDF page.
     *
     * Converts user coordinates (posX, posY) expressed in the visual coordinate system
     * into the internal PDF "page" space, taking into account:
     *  - Page rotation (/Rotate)
     *  - Effective page box (MediaBox/CropBox)
     *  - Responsive font scaling
     *
     * @param SetaPDF_Core_Document_Page $page Current PDF page
     * @param float $fontSizeUser Base font size before responsive scaling
     * @param float $posXUser Horizontal position (margin from left)
     * @param float $posYUser Vertical position (margin from top)
     * @param float $angleUser Desired text rotation in degrees
     *
     * @return array [xp, yp, angleDraw, fontSize] Transformed position, final rotation, and scaled font size
     * @throws Exception
     */
    private static function computeWatermarkPosition(
        SetaPDF_Core_Document_Page $page,
        float $fontSizeUser,
        float $posXUser,
        float $posYUser,
        float $angleUser
    ): array {
        // 1) Get the page dimensions (CropBox or MediaBox)
        $rect = $page->getBoundary(SetaPDF_Core_PageBoundaries::CROP_BOX, false)
            ?: $page->getBoundary(SetaPDF_Core_PageBoundaries::MEDIA_BOX, false);
        [$llx,$lly,$urx,$ury] = $rect->toPhp();
        $w = $urx - $llx;    // largeur en repère page
        $h = $ury - $lly;    // hauteur en repère page

        // 2) Normalize page rotation (0, 90, 180, 270)
        $rot   = ($page->getRotation() % 360 + 360) % 360;

        // 3) Adjust the drawing angle to compensate for page rotation
        $angleDraw = fmod($angleUser + $rot, 360.0);

        // 4) Determine visible height depending on rotation
        $Hview = ($rot === 90 || $rot === 270) ? $w : $h;

        // 5) Compute font size responsively
        $fontSize = self::calculateResponsiveNumericValue($page, $fontSizeUser);

        /**
         * Convert a point (xv, yv) expressed in the VIEW coordinate system
         * (as seen by the user) to PAGE coordinates (PDF internal space).
         *
         * xv,yv correspond to the **bottom-left** corner of the watermark box.
         */
        $viewToPage = function (float $xv, float $yv, float $fontSize) use ($rot, $w, $h): array {
            switch ($rot) {
                case 0:
                    return [$xv, $yv];
                case 90:
                    return [$w - $yv - $fontSize, $xv];
                case 180:
                    return [$w - $xv - $fontSize, $h - $yv - $fontSize];
                case 270:
                    return [$yv, $h - $xv - $fontSize];
            }
            return [$xv, $yv];
        };

        // 6) Compute margins responsively
        $marginX   = self::calculateResponsiveNumericValue($page, $posXUser);
        $marginY   = self::calculateResponsiveNumericValue($page, $posYUser);

        // 7) Compute visual-space coordinates (top-left reference)
        $xv = $marginX;
        $yv = $Hview - $marginY - $fontSize;

        // 8) Convert to PDF page coordinates
        [$xp, $yp] = $viewToPage($xv, $yv, $fontSize);

        return [$xp, $yp, $angleDraw, $fontSize];
    }

    /**
     * Render the watermark text onto the given canvas.
     *
     * @param SetaPDF_Core_Document $document The target PDF document
     * @param SetaPDF_Core_Canvas $canvas Canvas of the current page
     * @param string $text Watermark text content
     * @param string $fontId Standard font identifier
     * @param float $fontSize Font size in points
     * @param array $rgb01 RGB color array [r, g, b] with values between 0–1
     * @param float $opacity Text opacity between 0–1
     * @param float $angleDraw Final rotation angle in degrees
     * @param float $xp X position in page coordinates
     * @param float $yp Y position in page coordinates
     *
     * @return void
     */
    private static function drawWatermark(
        SetaPDF_Core_Document $document,
        SetaPDF_Core_Canvas $canvas,
        string $text,
        string $fontId,
        float $fontSize,
        array $rgb01,     // [r,g,b] entre 0..1
        float $opacity,   // 0..1
        float $angleDraw, // degrés
        float $xp,
        float $yp
    ): void {
        // Police
        $font = self::loadStandardFont($document, $fontId);

        // Opacité
        $gs = new SetaPDF_Core_Resource_ExtGState(
            SetaPDF_Core_Resource_ExtGState::createExtGStateDictionary()
        );
        $gs->setConstantOpacityNonStroking($opacity);
        $canvas->setGraphicState($gs, $document);

        // Texte
        $t = $canvas->text();
        $t->begin()->setFont($font, $fontSize);
        $t->setNonStrokingColor(
            new SetaPDF_Core_DataStructure_Color_Rgb(
                $rgb01[0],
                $rgb01[1],
                $rgb01[2]
            )
        );

        if ($angleDraw != 0.0) {
            $canvas->rotate($xp, $yp, $angleDraw);
        }

        $t->setTextMatrix(1, 0, 0, 1, $xp, $yp)
            ->showText($font->getCharCodes($text, 'UTF-8'))
            ->end();
    }

    /**
     * Load one of the 14 standard PDF fonts.
     *
     * @param SetaPDF_Core_Document $document PDF document instance
     * @param string $id Font identifier (case-insensitive)
     *
     * @return SetaPDF_Core_Font
     */
    private static function loadStandardFont(SetaPDF_Core_Document $document, string $id): SetaPDF_Core_Font
    {
        return match (strtolower($id)) {
            'courier'       => SetaPDF_Core_Font_Standard_Courier::create($document),
            'courierb'      => SetaPDF_Core_Font_Standard_CourierBold::create($document),
            'courierbi'     => SetaPDF_Core_Font_Standard_CourierBoldOblique::create($document),
            'courieri'      => SetaPDF_Core_Font_Standard_CourierOblique::create($document),
            'helvetica'     => SetaPDF_Core_Font_Standard_Helvetica::create($document),
            'helveticab'    => SetaPDF_Core_Font_Standard_HelveticaBold::create($document),
            'helveticabi'   => SetaPDF_Core_Font_Standard_HelveticaBoldOblique::create($document),
            'helveticai'    => SetaPDF_Core_Font_Standard_HelveticaOblique::create($document),
            'symbol'        => SetaPDF_Core_Font_Standard_Symbol::create($document),
            'timesb'        => SetaPDF_Core_Font_Standard_TimesBold::create($document),
            'timesbi'       => SetaPDF_Core_Font_Standard_TimesBoldItalic::create($document),
            'timesi'        => SetaPDF_Core_Font_Standard_TimesItalic::create($document),
            'times'         => SetaPDF_Core_Font_Standard_TimesRoman::create($document),
            'zapfdingbats'  => SetaPDF_Core_Font_Standard_ZapfDingbats::create($document),
            default         => SetaPDF_Core_Font_Standard_Helvetica::create($document),
        };
    }

    /**
     * Inspect the page content stream and neutralize any residual CTM (current transformation matrix)
     * left active at the end of the graphics operations.
     *
     * This ensures that any previous scaling, translation, or rotation
     * from existing content does not affect new drawings (e.g. watermarks).
     *
     * @param SetaPDF_Core_Document_Page $page Target PDF page
     * @param SetaPDF_Core_Canvas $canvas Page canvas to adjust
     *
     * @return void
     */
    private static function neutralizeEndCtm(SetaPDF_Core_Document_Page $page, SetaPDF_Core_Canvas $canvas): void
    {
        $s = $page->getContents()->getStream();
        if ($s === '') {
            return;
        }

        $off = 0;
        $len = strlen($s);
        $stack = [];
        $CTM = [1, 0, 0, 1, 0, 0];

        $WS = '/\G(?:\s+|%[^\r\n]*[\r\n])*/A';
        while ($off < $len) {
            if (preg_match($WS, $s, $m, 0, $off)) {
                $off += strlen($m[0]);
            }

            if (preg_match('/\Gq\b/A', $s, $m, 0, $off)) {
                $stack[] = $CTM;
                $off += strlen($m[0]);
                continue;
            }
            if (preg_match('/\GQ\b/A', $s, $m, 0, $off)) {
                if ($stack) {
                    $CTM = array_pop($stack);
                }
                $off += strlen($m[0]);
                continue;
            }

            if (
                preg_match(
                    '/\G([\-+]?\d*\.?\d+(?:[eE][\-+]?\d+)?)\s+' .
                    '([\-+]?\d*\.?\d+(?:[eE][\-+]?\d+)?)\s+' .
                    '([\-+]?\d*\.?\d+(?:[eE][\-+]?\d+)?)\s+' .
                    '([\-+]?\d*\.?\d+(?:[eE][\-+]?\d+)?)\s+' .
                    '([\-+]?\d*\.?\d+(?:[eE][\-+]?\d+)?)\s+' .
                    '([\-+]?\d*\.?\d+(?:[eE][\-+]?\d+)?)\s+cm\b/A',
                    $s,
                    $m,
                    0,
                    $off
                )
            ) {
                $M = [(float)$m[1], (float)$m[2], (float)$m[3], (float)$m[4], (float)$m[5], (float)$m[6]];
                $CTM = self::multiplyPdfMatrices($M, $CTM);   // PDF: CTM := M × CTM
                $off += strlen($m[0]);
                continue;
            }

            if (preg_match('/\G\S+/A', $s, $m, 0, $off)) {
                $off += strlen($m[0]);
            } else {
                break;
            }
        }

        if ($CTM !== [1, 0, 0, 1, 0, 0]) {
            $inv = self::invertPdfMatrix($CTM[0], $CTM[1], $CTM[2], $CTM[3], $CTM[4], $CTM[5]);
            if ($inv) {
                $canvas->addCurrentTransformationMatrix($inv[0], $inv[1], $inv[2], $inv[3], $inv[4], $inv[5]);
            }
        }
    }

    /**
     * Compute the inverse of a 2D PDF transformation matrix.
     *
     * A PDF transformation matrix has the form:
     *     [ a b c d e f ]
     * representing the affine transformation:
     *     x' = a*x + c*y + e
     *     y' = b*x + d*y + f
     *
     * This function returns the inverse matrix that undoes that transformation.
     * If the matrix is not invertible (determinant = 0), it returns null.
     *
     * @param float $a Matrix component a (scaleX / rotation)
     * @param float $b Matrix component b (shearY)
     * @param float $c Matrix component c (shearX)
     * @param float $d Matrix component d (scaleY / rotation)
     * @param float $e Matrix component e (translateX)
     * @param float $f Matrix component f (translateY)
     *
     * @return array<int,float>|null [a', b', c', d', e', f'] or null if non-invertible
     */
    private static function invertPdfMatrix(float $a, float $b, float $c, float $d, float $e, float $f): ?array
    {
        // Compute determinant of the 2×2 linear part
        $det = $a * $d - $b * $c;

        // Avoid division by zero (singular matrix)
        if (abs($det) < 1e-12) {
            return null;
        }

        // Invert the 2×2 portion
        $ia =  $d / $det;
        $ib = -$b / $det;
        $ic = -$c / $det;
        $id =  $a / $det;

        // Invert the translation (apply inverse linear transform to -[e,f])
        $ie = -($ia * $e + $ic * $f);
        $if = -($ib * $e + $id * $f);

        return [$ia,$ib,$ic,$id,$ie,$if];
    }

    /**
     * Multiply two 2D PDF transformation matrices.
     *
     * The multiplication follows PDF semantics:
     *     CTM := A × B
     * which means that matrix A is applied *after* matrix B
     * (post-multiplication order consistent with the PDF specification).
     *
     * Each matrix has the form [a, b, c, d, e, f]:
     *     [ a b 0 ]
     *     [ c d 0 ]
     *     [ e f 1 ]
     *
     * @param array<int,float> $A Left matrix [a, b, c, d, e, f]
     * @param array<int,float> $B Right matrix [a, b, c, d, e, f]
     *
     * @return array<int,float> The resulting matrix [a, b, c, d, e, f]
     */
    private static function multiplyPdfMatrices(array $A, array $B): array
    {
        [$a1,$b1,$c1,$d1,$e1,$f1] = $A;
        [$a2,$b2,$c2,$d2,$e2,$f2] = $B;

        // Standard 3×3 affine matrix multiplication (PDF convention)
        $a = $a1 * $a2 + $c1 * $b2;
        $b = $b1 * $a2 + $d1 * $b2;
        $c = $a1 * $c2 + $c1 * $d2;
        $d = $b1 * $c2 + $d1 * $d2;
        $e = $a1 * $e2 + $c1 * $f2 + $e1;
        $f = $b1 * $e2 + $d1 * $f2 + $f1;

        return [$a,$b,$c,$d,$e,$f];
    }

    /**
     * Calculate a responsive numeric value scaled to the current page dimensions.
     *
     *  Scales an original numeric value—designed for a reference (base) page format—to the
     *  actual width and height of the given page, ensuring the value adapts proportionally
     *  without exceeding either dimension.
     *
     * @param SetaPDF_Core_Document_Page $page The page whose size will be used for scaling.
     * @param float $originalValue The numeric value intended for the base format.
     * @param string $baseFormat A page format constant
     *                                                        (e.g. SetaPDF_Core_PageFormats::A4). Defaults to A4.
     * @param string $baseOrientation (Optional) One of
     *                                                        SetaPDF_Core_PageFormats::ORIENTATION_PORTRAIT
     *                                                        or SetaPDF_Core_PageFormats::ORIENTATION_LANDSCAPE.
     *                                                        Defaults to portrait.
     *
     * @return float The computed font size in points, scaled to the page.
     *
     * @throws Exception If the specified base format or orientation is invalid.
     */
    private static function calculateResponsiveNumericValue(
        SetaPDF_Core_Document_Page $page,
        float $originalValue,
        string $baseFormat = SetaPDF_Core_PageFormats::A4,
        string $baseOrientation = SetaPDF_Core_PageFormats::ORIENTATION_PORTRAIT
    ): float {
        // 1) Get the current page size (width, height) in user‐space units
        list($pageWidth, $pageHeight) = $page->getWidthAndHeight(
            SetaPDF_Core_PageBoundaries::CROP_BOX,
            true
        );

        // 2) Resolve the base format into [width, height]
        $baseSize = SetaPDF_Core_PageFormats::getFormat(
            $baseFormat,
            $baseOrientation
        );
        list($baseWidth, $baseHeight) = $baseSize;

        // 3) Compute a uniform scale factor so the text never overflows either dimension
        $scale = min($pageWidth / $baseWidth, $pageHeight / $baseHeight);

        // 4) Return the scaled font size
        return $originalValue * $scale;
    }
}
