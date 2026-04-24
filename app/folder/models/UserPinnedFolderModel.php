<?php

namespace Folder\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class UserPinnedFolderModel
{
    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function create(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['folder_id', 'user_id']);
        ValidatorModel::intVal($args, ['entity_id', 'user_id']);

        DatabaseModel::insert([
            'table'     => 'users_pinned_folders',
            'columnsValues' => [
                'folder_id'  => $args['folder_id'],
                'user_id'  => $args['user_id'],
            ]
        ]);

        return true;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getById(array $args = []): array
    {
        ValidatorModel::notEmpty($args, ['user_id']);
        ValidatorModel::intVal($args, ['user_id']);

        return DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['users_pinned_folders', 'folders'],
            'left_join' => ['users_pinned_folders.folder_id = folders.id'],
            'where'     => ['users_pinned_folders.user_id = ?'],
            'data'      => [$args['user_id']],
            'order_by'  => empty($args['orderBy']) ? ['label'] : $args['orderBy']
        ]);
    }

    /**
     * @param array $aArgs
     * @return array
     * @throws Exception
     */
    public static function get(array $aArgs): array
    {
        return DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users_pinned_folders'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
        ]);
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
            'table' => 'users_pinned_folders',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
