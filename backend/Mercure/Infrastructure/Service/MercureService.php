<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Mercure\Infrastructure\Service;

use Configuration\models\ConfigurationModel;
use DateTime;
use Exception;
use IndexingModel\models\IndexingModelModel;
use MaarchCourrier\Mercure\Domain\Port\MercureServiceInterface;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;

/**
 * @brief Mercure Service
 * @author dev@maarch.org
 */
class MercureService implements MercureServiceInterface
{
    /**
     * @throws Exception
     */
    public function isValidSetup(): bool|array
    {
        $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => 'config/ladConfiguration.json']);
        if (empty($ladConfiguration)) {
            return ['errors' => 'LAD configuration file does not exist'];
        }

        $mercureLadDirectory = $ladConfiguration['config']['mercureLadDirectory'];
        $mercureLadExecutable = $ladConfiguration['config']['mercureLadExecutable'] ?? 'Mercure5';
        $mercureLadConfigurationFile = $ladConfiguration['config']['mercureLadConfigurationFile']
            ?? 'MERCURE5_I1_LAD_COURRIER_v5.cfg';

        if (!is_dir($mercureLadDirectory)) {
            return ['errors' => 'Mercure module directory does not exist'];
        }

        if (
            !is_file($mercureLadDirectory . DIRECTORY_SEPARATOR . $mercureLadExecutable) ||
            !is_executable($mercureLadDirectory . DIRECTORY_SEPARATOR . $mercureLadExecutable)
        ) {
            return ['errors' => $mercureLadExecutable . ' exe is not present in the distribution or is not executable'];
        }

        if (
            !is_file($mercureLadDirectory . DIRECTORY_SEPARATOR . 'ugrep') ||
            !is_executable($mercureLadDirectory . DIRECTORY_SEPARATOR . 'ugrep')
        ) {
            return ['errors' => 'ugrep exe is not present in the distribution or is not executable'];
        }

        if (!is_file($mercureLadDirectory . DIRECTORY_SEPARATOR . $mercureLadConfigurationFile)) {
            return ['errors' => 'Mercure configuration file does not exist'];
        }

        $inputPath = $mercureLadDirectory . DIRECTORY_SEPARATOR . 'IN';
        $outputPath = $mercureLadDirectory . DIRECTORY_SEPARATOR . 'OUT';

        if (!is_writable($inputPath)) {
            return ['errors' => 'LAD input path is not writable'];
        }

        if (!is_writable($outputPath)) {
            return ['errors' => 'LAD output path is not writable'];
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function isEnabled(?int $modelId): bool
    {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        if (empty($configuration)) {
            return false;
        }

        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration['enabledLad'])) {
            return false;
        }

        if (!empty($modelId)) {
            return IndexingModelModel::getById(['id' => $modelId, 'select' => ['lad_processing']])['lad_processing'];
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function processLad(string $encodedResource, string $format): array
    {
        $customId = CoreConfigModel::getCustomId();

        $isValidSetup = $this->isValidSetup();
        if (isset($isValidSetup['errors'])) {
            return $isValidSetup;
        }

        $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => 'config/ladConfiguration.json']);

        $mercureLadDirectory = $ladConfiguration['config']['mercureLadDirectory'];
        $mercureLadExecutable = $ladConfiguration['config']['mercureLadExecutable'] ?? 'Mercure5';
        $mercureLadConfigurationFile = $ladConfiguration['config']['mercureLadConfigurationFile']
            ?? 'MERCURE5_I1_LAD_COURRIER_v5.cfg';

        $inputPath = $mercureLadDirectory . DIRECTORY_SEPARATOR . 'IN' .
            DIRECTORY_SEPARATOR . $customId . DIRECTORY_SEPARATOR;
        $outputPath = $mercureLadDirectory . DIRECTORY_SEPARATOR . 'OUT' .
            DIRECTORY_SEPARATOR . $customId . DIRECTORY_SEPARATOR;

        if (!is_dir($inputPath) && !mkdir($inputPath, 0755)) {
            return ['errors' => 'Error on input path creation for LAD process'];
        }

        if (!is_dir($outputPath) && !mkdir($outputPath, 0755)) {
            return ['errors' => 'Error on output path creation for LAD process'];
        }

        $tmpFilename = 'lad' . rand() . '_' . rand();

        $writeFileResult = file_put_contents(
            $inputPath . $tmpFilename . '.' . $format,
            base64_decode($encodedResource)
        );
        if (!$writeFileResult) {
            return ['errors' => 'Document writing error in input directory'];
        }

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'mercure',
            'level'     => 'INFO',
            'tableName' => '',
            'recordId'  => '',
            'eventType' => "LAD task",
            'eventId'   => "Launch LAD on file {$inputPath}{$tmpFilename}.{$format}"
        ]);

        $outXmlFilename = $mercureLadDirectory . DIRECTORY_SEPARATOR . 'OUT' .
            DIRECTORY_SEPARATOR . $customId . DIRECTORY_SEPARATOR . $tmpFilename . '.xml';

        $command = $mercureLadDirectory . DIRECTORY_SEPARATOR . $mercureLadExecutable . ' '
            . $inputPath . $tmpFilename . '.' . $format . ' ' . $outXmlFilename . ' '
            . $mercureLadDirectory . DIRECTORY_SEPARATOR . $mercureLadConfigurationFile;

        exec($command . ' 2>&1', $output, $return);

        $ladResult = [];

        if ($return == 0) {
            $mappingMercure = $ladConfiguration['mappingLadFields'];
            $outputXml = CoreConfigModel::getXmlLoaded(['path' => $outXmlFilename]);
            $mandatoryFields = [
                'subject',
                'documentDate',
                'contactIdx'
            ];

            foreach ($mandatoryFields as $f) {
                $ladResult[$f] = "";
            }
            if ($outputXml) {
                foreach ($outputXml->field as $field) {
                    $nameAttributeKey = 'n';
                    $nameAttribute = (string)$field->attributes()->$nameAttributeKey;
                    $disabledField = false;
                    $normalizationRule = '';
                    $normalizationFormat = null;

                    if (isset($mappingMercure[$nameAttribute])) {
                        if (isset($mappingMercure[$nameAttribute]['disabled'])) {
                            $disabledField = $mappingMercure[$nameAttribute]['disabled'];
                        }
                        if (isset($mappingMercure[$nameAttribute]['normalizationRule'])) {
                            $normalizationRule = $mappingMercure[$nameAttribute]['normalizationRule'];
                        }
                        if (isset($mappingMercure[$nameAttribute]['normalizationFormat'])) {
                            $normalizationFormat = $mappingMercure[$nameAttribute]['normalizationFormat'];
                        }
                        if (isset($mappingMercure[$nameAttribute]['key'])) {
                            $nameAttribute = $mappingMercure[$nameAttribute]['key'];
                        }
                    }

                    if (
                        !$disabledField &&
                        (!array_key_exists($nameAttribute, $ladResult) || empty($ladResult[$nameAttribute]))
                    ) {
                        $ladResult[$nameAttribute] = $this->normalizeField(
                            (string)$field[0],
                            $normalizationRule,
                            $normalizationFormat
                        );
                    }
                }

                foreach ($outputXml->SenderContact as $contact) {
                    $ladResult["contactIdx"] = (string)$contact->Idx[0];
                }
            } else {
                return ['errors' => 'Output XML  LAD file doesn\'t exists'];
            }

            if (is_file($inputPath . $tmpFilename . '.' . $format)) {
                unlink($inputPath . $tmpFilename . '.' . $format);
            }

            //Suppression du fichier xml
            unlink($outXmlFilename);
        } else {
            if (is_file($outXmlFilename)) {
                $outputXml = CoreConfigModel::getXmlLoaded(['path' => $outXmlFilename]);
                foreach ($outputXml->status as $status) {
                    if (str_contains(strtolower($status), 'quota exceeded')) {
                        return ['errors' => 'Number of LAD request exceeded, please contact Maarch'];
                    }
                }
            }
            $tabErrors = [];

            $tagsErrToCheck = [
                'not found',
                'error',
                'permission denied',
                'sh: 1'
            ];

            foreach ($output as $numLine => $lineOutput) {
                if ($this->contains($lineOutput, $tagsErrToCheck)) {
                    $tabErrors[] = "[" . $numLine . "]" . $lineOutput;
                }
            }

            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'mercure',
                'level'     => 'ERROR',
                'tableName' => '',
                'recordId'  => '',
                'eventType' => "LAD task",
                'eventId'   => "LAD task error on file {$inputPath}{$tmpFilename} . {$format}," .
                    " return : {$return}, errors : " . implode(",", $tabErrors)
            ]);
            return ['errors' => $tabErrors, 'output' => $output, 'return' => $return, 'cmd' => $command];
        }

        return $ladResult;
    }

    private function contains(string $strToCheck, array $arrTags): bool
    {
        foreach ($arrTags as $tag) {
            if (stripos($strToCheck, $tag) !== false) {
                return true;
            }
        }
        return false;
    }

    private function normalizeField(
        string $fieldContent,
        string $normalizationRule,
        string $normalizationFormat = null
    ): string {
        switch ($normalizationRule) {
            case 'DATE':
                $result = $this->normalizeDate($fieldContent, $normalizationFormat);
                break;
            default:
                $result = $fieldContent;
                break;
        }

        return $result;
    }

    private function normalizeDate(string $content, string $format): string
    {
        $result = strtolower($content);
        $result = str_replace(" ", "", $result);
        $result = $this->stripAccents($result);
        $result = $this->replaceMonth($result);

        $result = $this->getElementsDate($result);
        if (!$result) {
            return "";
        }

        $date = new DateTime($result['year'] . "-" . $result['month'] . "-" . $result['day']);

        return $date->format($format);
    }

    /**
     * @param string $dateString
     *
     * @return array|false
     */
    private function getElementsDate(string $dateString): bool|array
    {
        $strPattern = "/([0-9]|01|02|03|04|05|06|07|08|09|10|11|12|13|14|15|16|17|18|19|20|21|22|23|" .
            "24|25|26|27|28|29|30|31|premier|un|deux|trois|quatre|cinq|six|sept|huit|neuf|dix|onze)" .
            "\s?\.?\\\\?\/?-?_?(12|11|10|09|08|07|06|05|04|03|02|01|décembre|decembre|novembre|octobre|septembre|" .
            "aout|août|juillet|juin|mai|avril|mars|fevrier|février|janvier)\s?\.?\\\\?\/?-?_?(20[0-9][0-9])/m";
        preg_match_all($strPattern, $dateString, $matches, PREG_SET_ORDER, 0);

        $dateElements = [];
        if (!empty($matches[0][1])) {
            $dateElements['day'] = $matches[0][1];
            $dateElements['month'] = $matches[0][2];
            $dateElements['year'] = $matches[0][3];
            return $dateElements;
        }
        return false;
    }

    /**
     * Remove accents from a string.
     *
     * @param string $content The input string from which to strip accents.
     *
     * @return string The string with accents removed.
     */
    private function stripAccents(string $content): string
    {
        $search = [
            'À',
            'Á',
            'Â',
            'Ã',
            'Ä',
            'Å',
            'Ç',
            'È',
            'É',
            'Ê',
            'Ë',
            'Ì',
            'Í',
            'Î',
            'Ï',
            'Ò',
            'Ó',
            'Ô',
            'Õ',
            'Ö',
            'Ù',
            'Ú',
            'Û',
            'Ü',
            'Ý',
            'à',
            'á',
            'â',
            'ã',
            'ä',
            'å',
            'ç',
            'è',
            'é',
            'ê',
            'ë',
            'ì',
            'í',
            'î',
            'ï',
            'ð',
            'ò',
            'ó',
            'ô',
            'õ',
            'ö',
            'ù',
            'ú',
            'û',
            'ü',
            'ý',
            'ÿ'
        ];
        $replace = [
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'C',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'Y',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'c',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y'
        ];

        return str_replace($search, $replace, $content);
    }

    /**
     * @param string $dateString
     * @return string
     */
    private function replaceMonth(string $dateString): string
    {
        $search = [
            'janvier',
            'janv',
            'fevrier',
            'fev',
            'mars',
            'mar',
            'avril',
            'avr',
            'mai',
            'juin',
            'juillet',
            'juil',
            'aout',
            'aou',
            'septembre',
            'sept',
            'octobre',
            'oct',
            'novembre',
            'nov',
            'decembre',
            'dec'
        ];
        $replace = [
            '01',
            '01',
            '02',
            '02',
            '03',
            '03',
            '04',
            '04',
            '05',
            '06',
            '07',
            '07',
            '08',
            '08',
            '09',
            '09',
            '10',
            '10',
            '11',
            '11',
            '12',
            '12'
        ];

        return str_replace($search, $replace, $dateString);
    }
}
