<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Priority Abstract Model
 * @author dev@maarch.org
 */

namespace Priority\models;

use Exception;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

abstract class PriorityModelAbstract
{
    /**
     * @throws Exception
     */
    public static function get(array $aArgs = []): array
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($aArgs, ['limit', 'offset']);

        $aReturn = DatabaseModel::select([
            'select'   => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'    => ['priorities'],
            'where'    => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'     => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by' => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'offset'   => empty($aArgs['offset']) ? 0 : $aArgs['offset'],
            'limit'    => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);

        return $aReturn;
    }

    /**
     * @throws Exception
     */
    public static function getById(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['id']);
        ValidatorModel::arrayType($aArgs, ['select']);

        $aPriority = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['priorities'],
            'where'  => ['id = ?'],
            'data'   => [$aArgs['id']]
        ]);

        if (empty($aPriority[0])) {
            return [];
        }

        return $aPriority[0];
    }

    /**
     * @throws Exception
     */
    public static function create(array $aArgs): string
    {
        ValidatorModel::notEmpty($aArgs, ['label', 'color']);
        ValidatorModel::stringType($aArgs, ['label', 'color']);
        ValidatorModel::intVal($aArgs, ['delays']);

        $id = CoreConfigModel::uniqueId();
        DatabaseModel::insert([
            'table'         => 'priorities',
            'columnsValues' => [
                'id'     => $id,
                'label'  => $aArgs['label'],
                'color'  => $aArgs['color'],
                'delays' => $aArgs['delays'],
            ]
        ]);

        return $id;
    }

    /**
     * @throws Exception
     */
    public static function update(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'label', 'color']);
        ValidatorModel::stringType($aArgs, ['id', 'label', 'color']);
        ValidatorModel::intVal($aArgs, ['delays']);

        DatabaseModel::update([
            'table' => 'priorities',
            'set'   => [
                'label'  => $aArgs['label'],
                'color'  => $aArgs['color'],
                'delays' => $aArgs['delays'],
            ],
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public static function updateOrder(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['order']);

        DatabaseModel::update([
            'table' => 'priorities',
            'set'   => [
                '"order"' => $aArgs['order']
            ],
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public static function delete(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['id']);

        DatabaseModel::delete([
            'table' => 'priorities',
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        return true;
    }
}
