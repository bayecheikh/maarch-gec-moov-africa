<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief List Template Model
 * @author dev@maarch.org
 */

namespace Entity\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class ListTemplateItemModel
{
    /**
     * @throws Exception
     */
    public static function get(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data']);

        $items = DatabaseModel::select([
            'select'   => $args['select'] ?? [1],
            'table'    => ['list_templates_items'],
            'where'    => $args['where'] ?? [],
            'data'     => $args['data'] ?? [],
            'order_by' => $args['orderBy'] ?? []
        ]);

        return $items;
    }

    /**
     * @throws Exception
     */
    public static function create(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['list_template_id', 'item_id', 'item_type', 'item_mode']);
        ValidatorModel::stringType($args, ['item_type', 'item_mode']);
        ValidatorModel::intVal($args, ['list_template_id', 'item_id', 'sequence']);

        DatabaseModel::insert([
            'table'         => 'list_templates_items',
            'columnsValues' => [
                'list_template_id' => $args['list_template_id'],
                'item_id'          => $args['item_id'],
                'item_type'        => $args['item_type'],
                'item_mode'        => $args['item_mode'],
                'sequence'         => $args['sequence']
            ]
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public static function update(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['set', 'where', 'data']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'list_templates_items',
            'set'   => $args['set'],
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public static function delete(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'list_templates_items',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
