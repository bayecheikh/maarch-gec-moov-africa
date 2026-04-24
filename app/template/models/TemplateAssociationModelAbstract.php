<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Template Association Model Abstract
 * @author dev@maarch.org
 */

namespace Template\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

abstract class TemplateAssociationModelAbstract
{
    /**
     * @param  array  $aArgs
     *
     * @return array
     * @throws Exception
     */
    public static function get(array $aArgs = []): array
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($aArgs, ['limit']);

        $aTemplates = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['templates_association'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);

        return $aTemplates;
    }

    /**
     * @param  array  $aArgs
     *
     * @return bool
     * @throws Exception
     */
    public static function create(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['entityId', 'templateId']);
        ValidatorModel::stringType($aArgs, ['entityId']);
        ValidatorModel::intVal($aArgs, ['templateId']);

        DatabaseModel::insert([
            'table'         => 'templates_association',
            'columnsValues' => [
                'template_id'               => $aArgs['templateId'],
                'value_field'               => $aArgs['entityId']
            ]
        ]);

        return true;
    }

    /**
     * @param  array  $aArgs
     *
     * @return bool
     * @throws Exception
     */
    public static function update(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['set', 'where', 'data']);
        ValidatorModel::arrayType($aArgs, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'templates_association',
            'set'   => $aArgs['set'],
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }

    /**
     * @param  array  $aArgs
     *
     * @return bool
     * @throws Exception
     */
    public static function delete(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['where', 'data']);
        ValidatorModel::arrayType($aArgs, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'templates_association',
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }
}
