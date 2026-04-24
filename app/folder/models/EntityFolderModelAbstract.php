<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   FolderModelAbstract
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace Folder\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class EntityFolderModelAbstract
{
    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function get(array $args): array
    {
        ValidatorModel::notEmpty($args, ['select']);
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy', 'groupBy']);
        ValidatorModel::intType($args, ['limit']);

        return DatabaseModel::select([
            'select'    => $args['select'],
            'table'     => ['entities_folders'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'order_by'  => empty($args['orderBy']) ? [] : $args['orderBy'],
            'limit'     => empty($args['limit']) ? 0 : $args['limit'],
            'groupBy'   => empty($args['groupBy']) ? [] : $args['groupBy'],
        ]);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getEntitiesByFolderId(array $args): array
    {
        ValidatorModel::notEmpty($args, ['folder_id']);
        ValidatorModel::intVal($args, ['folder_id']);

        return DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['entities_folders', 'entities'],
            'left_join' => ['entities_folders.entity_id = entities.id'],
            'where'     => ['folder_id = ?', 'entities_folders.entity_id is not null', 'keyword is null'],
            'data'      => [$args['folder_id']]
        ]);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getKeywordsByFolderId(array $args): array
    {
        ValidatorModel::notEmpty($args, ['folder_id']);
        ValidatorModel::intVal($args, ['folder_id']);

        return DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['entities_folders'],
            'where'     => ['folder_id = ?', 'entity_id is null', 'keyword is not null'],
            'data'      => [$args['folder_id']]
        ]);
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function create(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['folder_id']);
        ValidatorModel::intVal($args, ['entity_id', 'folder_id']);
        ValidatorModel::boolType($args, ['edition']);
        ValidatorModel::stringType($args, ['keyword']);

        DatabaseModel::insert([
            'table'     => 'entities_folders',
            'columnsValues' => [
                'folder_id' => $args['folder_id'],
                'entity_id' => $args['entity_id'] ?? null,
                'edition'   => empty($args['edition']) ? 'false' : 'true',
                'keyword'   => $args['keyword'] ?? null
            ]
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function deleteByFolderId(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['folder_id']);
        ValidatorModel::intVal($args, ['folder_id']);

        DatabaseModel::delete([
            'table' => 'entities_folders',
            'where' => ['folder_id = ?'],
            'data'  => [$args['folder_id']]
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
            'table' => 'entities_folders',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
