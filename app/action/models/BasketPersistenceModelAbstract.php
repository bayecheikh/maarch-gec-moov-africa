<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
* @brief Basket Persistence Model
* @author  dev@maarch.org
*/

namespace Action\models;

use Exception;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

abstract class BasketPersistenceModelAbstract
{
    /**
     * @param array $aArgs
     *
     * @return true
     * @throws Exception
     */
    public static function create(array $aArgs): bool
    {
        DatabaseModel::insert([
            'table'         => 'basket_persistent_mode',
            'columnsValues' => $aArgs
        ]);

        return true;
    }

    /**
     * @param array $aArgs
     *
     * @return true
     * @throws Exception
     */
    public static function delete(array $aArgs): bool
    {
        ValidatorModel::notEmpty($aArgs, ['where', 'data']);
        ValidatorModel::arrayType($aArgs, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'basket_persistent_mode',
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }
}
