<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Convert Thumbnail Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentConversion\Infrastructure\Service;

use Imagick;
use MaarchCourrier\DocumentConversion\Domain\Port\ConvertThumbnailServiceInterface;
use SrcCore\models\CoreConfigModel;

class ConvertThumbnailService implements ConvertThumbnailServiceInterface
{
    public function convertOnePageFromFileContent(string $fileContent, string $fileFormat, int $page): array
    {
        $tmpPath = CoreConfigModel::getTmpPath();
        $tmpFileName = "converting_to_{$fileFormat}_" . rand() . '_' . rand();
        $tmpFilePath = $tmpPath . $tmpFileName . ".$fileFormat";
        file_put_contents($tmpFilePath, $fileContent);

        $filename = pathinfo($tmpFilePath, PATHINFO_FILENAME);

        $img = new Imagick();
        $img->pingImage($tmpFilePath);
        $pageCount = $img->getNumberImages();
        if ($pageCount < $page) {
            return ['errors' => "Page does not exist"];
        }

        $outputFileNameOnTmp = "output_" . rand() . "_$filename";

        $convertPage = $page - 1;
        $command = "convert -density 500x500 -quality 100 -resize 1000x -background white -alpha remove " .
            escapeshellarg($tmpFilePath) . "[$convertPage] " .
            escapeshellarg("{$tmpPath}{$outputFileNameOnTmp}.png");
        exec($command . ' 2>&1', $output, $return);

        $content = file_get_contents("{$tmpPath}{$outputFileNameOnTmp}.png");

        return ['fileContent' => $content, 'pageCount' => $pageCount];
    }
}
