<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Status Images Model
* @author dev@maarch.org
*/

namespace Status\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

abstract class StatusImagesModelAbstract
{
    /**
     * @param  array  $aArgs
     *
     * @return array
     * @throws Exception
     */
    public static function getStatusImages(array $aArgs = []): array
    {
        ValidatorModel::arrayType($aArgs, ['select']);

        return DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['status_images'],
            'order_by'  => ['id']
        ]);
    }
}
