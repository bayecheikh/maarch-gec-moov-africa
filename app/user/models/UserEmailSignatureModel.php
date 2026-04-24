<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Email Signature Model
 * @author dev@maarch.org
 */

namespace User\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class UserEmailSignatureModel
{
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

        $signature = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['users_email_signatures'],
            'where'     => ['id = ?'],
            'data'      => [$args['id']],
        ]);

        if (empty($signature[0])) {
            return [];
        }

        return $signature[0];
    }

    /**
     * @param  array  $args
     *
     * @return bool
     * @throws Exception
     */
    public static function create(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['userId', 'title', 'htmlBody']);
        ValidatorModel::stringType($args, ['title', 'htmlBody']);
        ValidatorModel::intVal($args, ['userId']);

        DatabaseModel::insert([
            'table'         => 'users_email_signatures',
            'columnsValues' => [
                'user_id'   => $args['userId'],
                'title'     => $args['title'],
                'html_body' => $args['htmlBody']
            ]
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
        ValidatorModel::notEmpty($args, ['id', 'title', 'htmlBody']);
        ValidatorModel::stringType($args, ['title', 'htmlBody']);
        ValidatorModel::intVal($args, ['id', 'userId']);

        DatabaseModel::update([
            'table'     => 'users_email_signatures',
            'set'       => [
                'title'     => $args['title'],
                'html_body' => $args['htmlBody'],
            ],
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$args['userId'], $args['id']]
        ]);

        return true;
    }

    /**
     * @param  array  $args
     *
     * @return bool
     * @throws Exception
     */
    public static function delete(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['id', 'userId']);
        ValidatorModel::intVal($args, ['id', 'userId']);

        DatabaseModel::delete([
            'table'     => 'users_email_signatures',
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$args['userId'], $args['id']]
        ]);

        return true;
    }

    /**
     * @param  array  $args
     *
     * @return array
     * @throws Exception
     */
    public static function getByUserId(array $args): array
    {
        ValidatorModel::notEmpty($args, ['userId']);
        ValidatorModel::intVal($args, ['userId']);

        $aReturn = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['users_email_signatures'],
            'where'     => ['user_id = ?'],
            'data'      => [$args['userId']],
            'order_by'  => ['id']
        ]);

        return $aReturn;
    }
}
