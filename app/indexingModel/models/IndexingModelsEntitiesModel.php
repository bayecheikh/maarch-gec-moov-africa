<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Indexing Models Entities
 * @author dev@maarch.org
 */

namespace IndexingModel\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class IndexingModelsEntitiesModel
{
    public const ALL_ENTITIES = 'ALL_ENTITIES';

    /**
     * @throws Exception
     */
    public static function create(array $args): int
    {
        ValidatorModel::notEmpty($args, ['model_id']);
        ValidatorModel::stringType($args, ['entity_id', 'keyword']);
        ValidatorModel::intVal($args, ['model_id']);


        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'indexing_models_entities_id_seq']);

        DatabaseModel::insert([
            'table'         => 'indexing_models_entities',
            'columnsValues' => [
                'id'        => $nextSequenceId,
                'model_id'  => $args['model_id'],
                'entity_id' => $args['entity_id'] ?? null,
                'keyword'   => $args['keyword'] ?? null
            ]
        ]);

        return $nextSequenceId;
    }

    /**
     * @throws Exception
     */
    public static function get(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit']);

        $models = DatabaseModel::select([
            'select'   => empty($args['select']) ? ['*'] : $args['select'],
            'table'    => ['indexing_models_entities'],
            'where'    => empty($args['where']) ? [] : $args['where'],
            'data'     => empty($args['data']) ? [] : $args['data'],
            'order_by' => empty($args['orderBy']) ? [] : $args['orderBy'],
            'limit'    => empty($args['limit']) ? 0 : $args['limit']
        ]);

        return $models;
    }

    /**
     * @throws Exception
     */
    public static function getById(array $args): array
    {
        ValidatorModel::notEmpty($args, ['id']);
        ValidatorModel::intVal($args, ['id']);
        ValidatorModel::arrayType($args, ['select']);

        $model = DatabaseModel::select([
            'select' => empty($args['select']) ? ['*'] : $args['select'],
            'table'  => ['indexing_models_entities'],
            'where'  => ['id = ?'],
            'data'   => [$args['id']],
        ]);

        if (empty($model[0])) {
            return [];
        }

        return $model[0];
    }

    /**
     * @throws Exception
     */
    public static function getByModelId(array $args): array
    {
        ValidatorModel::notEmpty($args, ['model_id']);
        ValidatorModel::intVal($args, ['model_id']);
        ValidatorModel::arrayType($args, ['select']);

        $model = DatabaseModel::select([
            'select' => empty($args['select']) ? ['*'] : $args['select'],
            'table'  => ['indexing_models_entities'],
            'where'  => ['model_id = ?'],
            'data'   => [$args['model_id']],
        ]);

        return $model;
    }

    /**
     * @throws Exception
     */
    public static function getByEntityId(array $args): array
    {
        ValidatorModel::notEmpty($args, ['entity_id']);
        ValidatorModel::stringType($args, ['entity_id']);
        ValidatorModel::arrayType($args, ['select']);

        $model = DatabaseModel::select([
            'select' => empty($args['select']) ? ['*'] : $args['select'],
            'table'  => ['indexing_models_entities'],
            'where'  => ['entity_id = ?'],
            'data'   => [$args['entity_id']],
        ]);

        return $model;
    }

    /**
     * @throws Exception
     */
    public static function getByEntityIdOrKeyword(array $args): array
    {
        ValidatorModel::notEmpty($args, ['entity_id', 'keyword']);
        ValidatorModel::stringType($args, ['entity_id', 'keyword']);
        ValidatorModel::arrayType($args, ['select']);

        $model = DatabaseModel::select([
            'select' => empty($args['select']) ? ['*'] : $args['select'],
            'table'  => ['indexing_models_entities'],
            'where'  => ['entity_id = ? OR keyword = ?'],
            'data'   => [$args['entity_id'], $args['keyword']],
        ]);

        return $model;
    }

    /**
     * @throws Exception
     */
    public static function getModelIdsFromEntityWithKeyword(array $args): array
    {
        ValidatorModel::notEmpty($args, ['entity_id', 'keyword']);
        ValidatorModel::stringType($args, ['entity_id', 'keyword']);

        $model = DatabaseModel::select([
            'select'    => ['distinct(IM.id)'],
            'table'     => ['indexing_models_entities as IME', 'indexing_models as IM'],
            'left_join' => [
                'IME.model_id = IM.id AND IM.private = false and IM.enabled = true and IM.id in '
                . '(select model_id from indexing_models_entities where entity_id = ?  OR keyword = ?)'
            ],
            'where'     => ['IM.id IS NOT NULL'],
            'data'      => [$args['entity_id'], $args['keyword']]
        ]);

        return $model;
    }

    /**
     * @throws Exception
     */
    public static function update(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['set', 'where', 'data']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'indexing_models_entities',
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
            'table' => 'indexing_models_entities',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
