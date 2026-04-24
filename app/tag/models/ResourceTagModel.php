<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource Tag Model
 * @author dev@maarch.org
 */

namespace Tag\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class ResourceTagModel
{
    /**
     * @param  array  $aArgs
     *
     * @return array
     * @throws Exception
     */
    public static function get(array $aArgs): array
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($aArgs, ['limit']);

        return DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['resources_tags'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'groupBy'   => empty($aArgs['groupBy']) ? [] : $aArgs['groupBy'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);
    }

    /**
     * @param  array  $args
     *
     * @return bool
     * @throws Exception
     */
    public static function create(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['res_id', 'tag_id']);
        ValidatorModel::intVal($args, ['res_id', 'tag_id']);

        DatabaseModel::insert([
            'table'         => 'resources_tags',
            'columnsValues' => [
                'res_id'    => $args['res_id'],
                'tag_id'    => $args['tag_id']
            ]
        ]);

        return true;
    }

    /**
     * @param  array  $args
     *
     * @return bool
     * @throws Exception
     */
    public static function update(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table'     => 'resources_tags',
            'set'       => empty($args['set']) ? [] : $args['set'],
            'where'     => $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data']
        ]);

        return true;
    }

    /**
     * @param  array  $args
     *
     * @return bool
     * @throws Exception
     */
    public static function delete(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'resources_tags',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
