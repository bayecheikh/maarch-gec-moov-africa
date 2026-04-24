<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Validator Model
 * @author dev@maarch.org
 */

namespace SrcCore\models;

use Exception;
use Respect\Validation\Validator;

class ValidatorModel
{
    /**
     * @throws Exception
     */
    public static function notEmpty(array $args, array $keys): void
    {
        if (!Validator::arrayType()->notEmpty()->validate($args)) {
            throw new Exception('First argument must be a non empty array');
        }
        foreach ($keys as $key) {
            if (Validator::stringType()->validate($args[$key]) && trim($args[$key]) == '' && $args[$key] != '') {
                $args[$key] .= 'NOT_EMPTY';
            }
            if (Validator::stringType()->validate($args[$key]) && trim($args[$key]) == '0' && $args[$key] != '0') {
                $args[$key] .= 'NOT_EMPTY';
            }
            if (!Validator::notEmpty()->validate($args[$key])) {
                throw new Exception("Argument $key is empty");
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function intVal(array $args, array $aKeys): void
    {
        foreach ($aKeys as $key) {
            if (!isset($args[$key])) {
                continue;
            }
            if (!Validator::intVal()->validate($args[$key])) {
                throw new Exception("Argument $key is not an integer (value)");
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function json(array $args, array $aKeys): void
    {
        foreach ($aKeys as $key) {
            if (!isset($args[$key])) {
                continue;
            }
            if (!Validator::json()->validate($args[$key])) {
                throw new Exception("Argument $key is not a json (value)");
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function intType(array $args, array $aKeys): void
    {
        foreach ($aKeys as $key) {
            if (!isset($args[$key])) {
                continue;
            }
            if (!Validator::intType()->validate($args[$key])) {
                throw new Exception("Argument $key is not an integer (type)");
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function stringType(array $args, array $aKeys): void
    {
        foreach ($aKeys as $key) {
            if (!isset($args[$key])) {
                continue;
            }
            if (!Validator::stringType()->validate($args[$key])) {
                throw new Exception("Argument $key is not a string (type)");
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function arrayType(array $args, array $aKeys): void
    {
        foreach ($aKeys as $key) {
            if (!isset($args[$key])) {
                continue;
            }
            if (!Validator::arrayType()->validate($args[$key])) {
                throw new Exception("Argument $key is not an array (type)");
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function boolType(array $args, array $aKeys): void
    {
        foreach ($aKeys as $key) {
            if (!isset($args[$key])) {
                continue;
            }
            if (!Validator::boolType()->validate($args[$key])) {
                throw new Exception("Argument $key is not a boolean (type)");
            }
        }
    }
}
