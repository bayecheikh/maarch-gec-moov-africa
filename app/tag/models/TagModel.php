<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Tag Model
 * @author dev@maarch.org
 */

namespace Tag\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class TagModel
{
    /**
     * @param  array  $args
     *
     * @return array
     * @throws Exception
     */
    public static function get(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit']);

        return DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['tags'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'order_by'  => empty($args['orderBy']) ? [] : $args['orderBy'],
            'groupBy'   => empty($args['groupBy']) ? [] : $args['groupBy'],
            'limit'     => empty($args['limit']) ? 0 : $args['limit']
        ]);
    }

    /**
     * @param  array  $args
     *
     * @return array
     * @throws Exception
     */
    public static function getById(array $args): array
    {
        ValidatorModel::notEmpty($args, ['id']);
        ValidatorModel::intVal($args, ['id']);
        ValidatorModel::arrayType($args, ['select']);

        $tag = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['tags'],
            'where'     => ['id = ?'],
            'data'      => [$args['id']],
        ]);

        if (empty($tag[0])) {
            return [];
        }

        return $tag[0];
    }

    /**
     * @param  array  $args
     *
     * @return int
     * @throws Exception
     */
    public static function create(array $args): int
    {
        ValidatorModel::notEmpty($args, ['label']);
        ValidatorModel::stringType($args, ['label']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'tags_id_seq']);

        DatabaseModel::insert([
            'table'         => 'tags',
            'columnsValues' => [
                'id'          => $nextSequenceId,
                'label'       => $args['label'],
                'description' => $args['description'] ?? null,
                'parent_id'   => $args['parentId'] ?? null,
                'links'       => $args['links'] ?? json_encode('[]'),
                'usage'       => $args['usage'] ?? null
            ]
        ]);

        return $nextSequenceId;
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
            'table' => 'tags',
            'where' => $args['where'],
            'data'  => $args['data']
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
            'table'     => 'tags',
            'set'       => empty($args['set']) ? [] : $args['set'],
            'postSet'   => empty($args['postSet']) ? [] : $args['postSet'],
            'where'     => $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data']
        ]);

        return true;
    }
}
