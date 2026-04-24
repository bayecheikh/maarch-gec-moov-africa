<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Check Document Signature Field Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Service;

use SrcCore\interfaces\CheckDocumentSignatureFieldsInterface;
use SrcCore\models\CoreConfigModel;
use SetaPDF_Core_Document;
use SetaPDF_FormFiller;
use SetaPDF_FormFiller_Field_Signature;

class CheckDocumentSignatureFieldService implements CheckDocumentSignatureFieldsInterface
{
    public function checkDocumentSignatureFields(string $args): bool
    {
        $libPath = CoreConfigModel::getSetaPdfFormFillerLibrary();
        $alreadySigned = false;

        if (!empty($libPath) && is_file($libPath)) {
            require_once($libPath);

            $targetFile = CoreConfigModel::getTmpPath() . "tmp_file_{$GLOBALS['id']}_" . rand() .
                "_target_watermark.pdf";
            file_put_contents($targetFile, base64_decode($args));
            $document = SetaPDF_Core_Document::loadByFilename($targetFile);

            $formFiller = new SetaPDF_FormFiller($document);
            $fields = $formFiller->getFields();
            $allFields = $fields->getAll();

            foreach ($allFields as $field) {
                if (!$field instanceof SetaPDF_FormFiller_Field_Signature) {
                    continue;
                }

                // /V du champ signature (null si non signé)
                $sigDict = $field->getValue(); // en v2: ?PdfDictionary :contentReference[oaicite:2]{index=2}
                if ($sigDict) {
                    $alreadySigned = true;
                    break;
                }
            }

            // Release objects to free memory and cycled references.
            // After calling this method the instance of $document is unusable!
            $document->cleanUp();
            unlink($targetFile);
        }

        return $alreadySigned;
    }
}
