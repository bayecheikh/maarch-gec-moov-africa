<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 * @brief   DoctypeModelAbstract
 * @author  dev <dev@maarch.org>
 * @ingroup core
 */

namespace Doctype\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class DoctypeModelAbstract
{
    /**
     * @throws Exception
     */
    public static function get(array $aArgs = []): array
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($aArgs, ['limit']);

        $doctypes = DatabaseModel::select([
            'select'   => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'    => ['doctypes'],
            'where'    => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'     => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by' => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'limit'    => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);

        return $doctypes;
    }

    /**
     * @throws Exception
     */
    public static function getById(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        $aReturn = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['doctypes'],
            'where'  => ['type_id = ?'],
            'data'   => [$aArgs['id']]
        ]);

        if (empty($aReturn[0])) {
            return [];
        }

        return $aReturn[0];
    }

    /**
     * @throws Exception
     */
    public static function create(array $aArgs): int
    {
        ValidatorModel::notEmpty($aArgs, ['description', 'doctypes_first_level_id', 'doctypes_second_level_id']);
        ValidatorModel::intVal($aArgs, ['doctypes_first_level_id', 'doctypes_second_level_id']);

        $aArgs['type_id'] = DatabaseModel::getNextSequenceValue(['sequenceId' => 'doctypes_type_id_seq']);
        DatabaseModel::insert([
            'table'         => 'doctypes',
            'columnsValues' => $aArgs
        ]);

        return $aArgs['type_id'];
    }

    /**
     * @throws Exception
     */
    public static function update(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['type_id']);
        ValidatorModel::intVal($aArgs, ['type_id']);

        DatabaseModel::update([
            'table' => 'doctypes',
            'set'   => $aArgs,
            'where' => ['type_id = ?'],
            'data'  => [$aArgs['type_id']]
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public static function disabledFirstLevel(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['doctypes_first_level_id']);
        ValidatorModel::intVal($aArgs, ['doctypes_first_level_id']);

        DatabaseModel::update([
            'table' => 'doctypes',
            'set'   => $aArgs,
            'where' => ['doctypes_first_level_id = ?'],
            'data'  => [$aArgs['doctypes_first_level_id']]
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public static function disabledSecondLevel(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['doctypes_second_level_id']);
        ValidatorModel::intVal($aArgs, ['doctypes_second_level_id']);

        DatabaseModel::update([
            'table' => 'doctypes',
            'set'   => $aArgs,
            'where' => ['doctypes_second_level_id = ?'],
            'data'  => [$aArgs['doctypes_second_level_id']]
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public static function delete(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['type_id']);
        ValidatorModel::intVal($aArgs, ['type_id']);

        DatabaseModel::delete([
            'table' => 'doctypes',
            'where' => ['type_id = ?'],
            'data'  => [$aArgs['type_id']]
        ]);

        return true;
    }
}
