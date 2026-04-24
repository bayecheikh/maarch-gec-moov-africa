<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
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

class ListTemplateModel
{
    /**
     * @throws Exception
     */
    public static function get(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data']);

        $listTemplates = DatabaseModel::select([
            'select'   => $args['select'] ?? [1],
            'table'    => ['list_templates'],
            'where'    => $args['where'] ?? [],
            'data'     => $args['data'] ?? [],
            'order_by' => $args['orderBy'] ?? []
        ]);

        return $listTemplates;
    }

    /**
     * @throws Exception
     */
    public static function getById(array $args): array
    {
        ValidatorModel::notEmpty($args, ['id']);
        ValidatorModel::intVal($args, ['id']);
        ValidatorModel::arrayType($args, ['select']);

        $listTemplate = DatabaseModel::select([
            'select' => $args['select'] ?? [1],
            'table'  => ['list_templates'],
            'where'  => ['id = ?'],
            'data'   => [$args['id']]
        ]);

        if (empty($listTemplate[0])) {
            return [];
        }

        return $listTemplate[0];
    }

    /**
     * @throws Exception
     */
    public static function create(array $args): int
    {
        ValidatorModel::notEmpty($args, ['title', 'type']);
        ValidatorModel::stringType($args, ['title', 'type', 'description']);
        ValidatorModel::intVal($args, ['entity_id']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'list_templates_id_seq']);

        DatabaseModel::insert([
            'table'         => 'list_templates',
            'columnsValues' => [
                'id'          => $nextSequenceId,
                'title'       => $args['title'],
                'description' => $args['description'] ?? null,
                'type'        => $args['type'],
                'entity_id'   => $args['entity_id'] ?? null,
                'owner'       => $args['owner']
            ]
        ]);

        return $nextSequenceId;
    }

    /**
     * @throws Exception
     */
    public static function update(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['set', 'where', 'data']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'list_templates',
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
            'table' => 'list_templates',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public static function deleteNoItemsOnes(): bool
    {
        DatabaseModel::delete([
            'table' => 'list_templates',
            'where' => ['id not in (select DISTINCT(list_template_id) FROM list_templates_items)']
        ]);

        return true;
    }

    /**
     * @throws Exception
     */
    public static function getWithItems(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);

        $listTemplates = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['list_templates', 'list_templates_items'],
            'left_join' => ['list_templates.id = list_templates_items.list_template_id'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'order_by'  => empty($args['orderBy']) ? [] : $args['orderBy']
        ]);

        return $listTemplates;
    }

    /**
     * @throws Exception
     */
    public static function getTypes(array $aArgs = []): array
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data']);

        $aListTemplatesTypes = DatabaseModel::select([
            'select' => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'  => ['difflist_types'],
            'where'  => $aArgs['where'],
            'data'   => $aArgs['data']
        ]);

        return $aListTemplatesTypes;
    }

    /**
     * @throws Exception
     */
    public static function updateTypes(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['set', 'where', 'data']);
        ValidatorModel::arrayType($aArgs, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'difflist_types',
            'set'   => $aArgs['set'],
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }
}
