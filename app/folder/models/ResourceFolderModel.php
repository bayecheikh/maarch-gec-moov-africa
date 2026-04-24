<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ResourceFolderModel
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace Folder\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class ResourceFolderModel
{
    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function get(array $args): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit']);

        return DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['resources_folders'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'order_by'  => empty($args['orderBy']) ? [] : $args['orderBy'],
            'limit'     => empty($args['limit']) ? 0 : $args['limit']
        ]);
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function create(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['folder_id', 'res_id']);
        ValidatorModel::intVal($args, ['folder_id', 'res_id']);

        DatabaseModel::insert([
            'table'         => 'resources_folders',
            'columnsValues' => [
                'folder_id' => $args['folder_id'],
                'res_id'    => $args['res_id']
            ]
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function delete(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'resources_folders',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
