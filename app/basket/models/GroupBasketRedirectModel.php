<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief   Group Basket Redirect Model Abstract
* @author  dev@maarch.org
*/

namespace Basket\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class GroupBasketRedirectModel
{
    /**
     * @param array $aArgs
     *
     * @return array
     * @throws Exception
     */
    public static function get(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy']);

        return DatabaseModel::select([
            'select'   => $aArgs['select'],
            'table'    => ['groupbasket_redirect'],
            'where'    => $aArgs['where'],
            'data'     => $aArgs['data'],
            'order_by' => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy']
        ]);
    }

    /**
     * @param array $aArgs
     *
     * @return true
     * @throws Exception
     */
    public static function create(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'groupId', 'actionId', 'redirectMode']);
        ValidatorModel::stringType($aArgs, ['id', 'groupId', 'entityId', 'keyword', 'redirectMode']);
        ValidatorModel::intVal($aArgs, ['actionId']);

        DatabaseModel::insert([
            'table'         => 'groupbasket_redirect',
            'columnsValues' => [
                'action_id'     => $aArgs['actionId'],
                'group_id'      => $aArgs['groupId'],
                'basket_id'     => $aArgs['id'],
                'entity_id'     => $aArgs['entityId'],
                'keyword'       => $aArgs['keyword'],
                'redirect_mode' => $aArgs['redirectMode']
            ]
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     *
     * @return true
     * @throws Exception
     */
    public static function update(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['set', 'where', 'data']);
        ValidatorModel::arrayType($aArgs, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'groupbasket_redirect',
            'set'   => $aArgs['set'],
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }

    /**
     * @param array $args
     *
     * @return true
     * @throws Exception
     */
    public static function delete(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'groupbasket_redirect',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
