<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Authenticate Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Infrastructure;

use Exception;
use MaarchCourrier\Authentication\Domain\AuthenticateServiceInterface;
use SrcCore\models\DatabaseModel;

class AuthenticateService implements AuthenticateServiceInterface
{
    /**
     * @throws Exception
     */
    public function authentication(string $login, string $password): bool
    {
        $userPassword = DatabaseModel::select([
            'select' => ['password'],
            'table'  => ['users'],
            'where'  => [
                'lower(user_id) = lower(?)',
                'status in (?, ?)',
                '(locked_until is null OR locked_until < CURRENT_TIMESTAMP)'
            ],
            'data'   => [$login, 'OK', 'ABS']
        ]);

        if (empty($userPassword[0])) {
            return false;
        }

        return password_verify($password, $userPassword[0]['password']);
    }
}
