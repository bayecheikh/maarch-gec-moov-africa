<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Address Sector Model
 * @author dev@maarch.org
 */

namespace Contact\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class ContactAddressSectorModel
{
    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function get(array $args): array
    {
        ValidatorModel::notEmpty($args, ['select']);
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit']);

        $sectors = DatabaseModel::select([
            'select'   => $args['select'],
            'table'    => ['address_sectors'],
            'where'    => empty($args['where']) ? [] : $args['where'],
            'data'     => empty($args['data']) ? [] : $args['data'],
            'order_by' => empty($args['orderBy']) ? [] : $args['orderBy'],
            'offset'   => empty($args['offset']) ? 0 : $args['offset'],
            'limit'    => empty($args['limit']) ? 0 : $args['limit']
        ]);

        return $sectors;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getById(array $args): array
    {
        ValidatorModel::notEmpty($args, ['id', 'select']);
        ValidatorModel::intVal($args, ['id']);
        ValidatorModel::arrayType($args, ['select']);

        $sector = DatabaseModel::select([
            'select' => $args['select'],
            'table'  => ['address_sectors'],
            'where'  => ['id = ?'],
            'data'   => [$args['id']]
        ]);

        if (empty($sector[0])) {
            return [];
        }

        return $sector[0];
    }

    /**
     * @param array $args
     * @return int
     * @throws Exception
     */
    public static function create(array $args): int
    {
        ValidatorModel::notEmpty($args, ['label', 'abbreviation']);
        ValidatorModel::stringType($args, ['label', 'abbreviation']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'address_sectors_id_seq']);

        DatabaseModel::insert([
            'table'         => 'address_sectors',
            'columnsValues' => [
                'id'           => $nextSequenceId,
                'label'        => $args['label'],
                'abbreviation' => $args['abbreviation']
            ]
        ]);

        return $nextSequenceId;
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function update(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['set', 'where', 'data']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'address_sectors',
            'set'   => $args['set'],
            'where' => $args['where'],
            'data'  => $args['data']
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
            'table' => 'address_sectors',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
