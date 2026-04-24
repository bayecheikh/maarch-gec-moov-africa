<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Text Format Model
 * @author dev@maarch.org
 */

namespace SrcCore\models;

use DateTime;
use Exception;

class TextFormatModel
{
    /**
     * @param array $aArgs
     * @return string
     * @throws Exception
     */
    public static function normalize(array $aArgs): string
    {
        ValidatorModel::notEmpty($aArgs, ['string']);
        ValidatorModel::stringType($aArgs, ['string']);

        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        $replacements = [
            'À' => 'a',
            'Á' => 'a',
            'Â' => 'a',
            'Ã' => 'a',
            'Ä' => 'a',
            'Å' => 'a',
            'Æ' => 'ae',
            'Ç' => 'c',
            'È' => 'e',
            'É' => 'e',
            'Ê' => 'e',
            'Ë' => 'e',
            'Ì' => 'i',
            'Í' => 'i',
            'Î' => 'i',
            'Ï' => 'i',
            'Ð' => 'd',
            'Ñ' => 'n',
            'Ò' => 'o',
            'Ó' => 'o',
            'Ô' => 'o',
            'Õ' => 'o',
            'Ö' => 'o',
            'Ø' => 'o',
            'Ù' => 'u',
            'Ú' => 'u',
            'Û' => 'u',
            'Ü' => 'u',
            'Ý' => 'y',
            'Þ' => 'b',
            'ß' => 's',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'd',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'þ' => 'b',
            'ÿ' => 'y',
            'Ŕ' => 'R',
            'ŕ' => 'r',
            'œ' => 'oe',
            'Œ' => 'OE'
        ];

        $string = $aArgs['string'];
        $string = strtr($string, $replacements);
        return mb_strtolower($string, 'UTF-8');
    }

    /**
     * @param array $args
     * @return string|null
     * @throws Exception
     */
    public static function formatFilename(array $args): string|null
    {
        ValidatorModel::stringType($args, ['filename']);

        if (!empty($args['maxLength'])) {
            $args['filename'] = mb_substr($args['filename'], 0, $args['maxLength']);
        }

        // Replace line breaks with underscores
        $args['filename'] = str_replace(["\r\n", "\n", "\r"], "_", $args['filename']);

        return preg_replace(utf8_decode('@[\\/:*?"\'<>|,]@'), '_', $args['filename']);
    }

    /**
     * @param string|null $date
     * @param string|null $format
     *
     * @return string
     * @throws Exception
     */
    public static function formatDate(?string $date, ?string $format = null): string
    {
        if (empty($date)) {
            return '';
        }

        $date = new DateTime($date);

        if (!empty($format)) {
            return $date->format($format);
        }

        return $date->format('d-m-Y H:i');
    }

    /**
     * @param array $args
     * @return string
     * @throws Exception
     */
    public static function getEndDayDate(array $args): string
    {
        ValidatorModel::notEmpty($args, ['date']);
        ValidatorModel::stringType($args, ['date']);

        $date = new DateTime($args['date']);
        $date->setTime(23, 59, 59);

        return $date->format('d-m-Y H:i:s');
    }

    /**
     * @param array $aArgs
     * @return string
     * @throws Exception
     */
    public static function removeAccent(array $aArgs): string
    {
        ValidatorModel::notEmpty($aArgs, ['string']);
        ValidatorModel::stringType($aArgs, ['string', 'charset']);

        if (empty($aArgs['charset'])) {
            $aArgs['charset'] = 'utf-8';
        }

        $string = htmlentities($aArgs['string'], ENT_NOQUOTES, $aArgs['charset']);

        $string = preg_replace('#\&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring)\;#', '\1', $string);
        $string = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $string);
        $string = preg_replace('#\&[^;]+\;#', '', $string);

        return $string;
    }

    /**
     * @param string $html
     * @return string
     */
    public static function htmlWasher(string $html): string
    {
        $html = str_replace("<br/>", "\\n", $html);
        $html = str_replace("<br />", "\\n", $html);
        $html = str_replace("<br/>", "\\n", $html);
        $html = str_replace("&nbsp;", " ", $html);
        $html = str_replace("&eacute;", "\u00e9", $html);
        $html = str_replace("&egrave;", "\u00e8", $html);
        $html = str_replace("&ecirc;", "\00ea", $html);
        $html = str_replace("&agrave;", "\u00e0", $html);
        $html = str_replace("&acirc;", "\u00e2", $html);
        $html = str_replace("&icirc;", "\u00ee", $html);
        $html = str_replace("&ocirc;", "\u00f4", $html);
        $html = str_replace("&ucirc;", "\u00fb", $html);
        $html = str_replace("&acute;", "\u0027", $html);
        $html = str_replace("&deg;", "\u00b0", $html);
        return str_replace("&rsquo;", "\u2019", $html);
    }

    /**
     * @param array $aArgs
     * @return string
     * @throws Exception
     */
    public static function cutString(array $aArgs): string
    {
        ValidatorModel::notEmpty($aArgs, ['string']);
        ValidatorModel::stringType($aArgs, ['string']);
        ValidatorModel::intType($aArgs, ['max']);

        $string = $aArgs['string'];
        $max = $aArgs['max'];
        if (strlen($string) >= $max) {
            $string = substr($string, 0, $max);
            $espace = strrpos($string, " ");
            $string = substr($string, 0, $espace) . "...";
            return $string;
        } else {
            return $string;
        }
    }

    /**
     * @param string $subject
     * @return string
     */
    public static function snakeToCamel(string $subject): string
    {
        $subject = lcfirst(ucwords($subject, '_'));
        return str_replace('_', '', $subject);
    }

    /**
     * @param string $subject
     * @return string
     */
    public static function camelToSnake(string $subject): string
    {
        $snakeCaseSubject = '';
        foreach (str_split($subject) as $index => $character) {
            if ($index > 0 && strtoupper($character) == $character && !str_contains('0123456789_', $character)) {
                $character = strtolower($character);
                $character = '_' . $character;
            }
            $snakeCaseSubject .= $character;
        }
        return $snakeCaseSubject;
    }
}
