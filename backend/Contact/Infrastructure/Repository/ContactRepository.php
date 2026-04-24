<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Repository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Contact\Infrastructure\Repository;

use Exception;
use MaarchCourrier\Contact\Domain\Contact;
use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\Contact\Port\ContactRepositoryInterface;
use SrcCore\models\DatabaseModel;

class ContactRepository implements ContactRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function getById(int $id): ?ContactInterface
    {
        $contact = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['contacts'],
            'where'  => ['id = ?'],
            'data'   => [$id],
        ]);

        if (empty($contact[0])) {
            return null;
        }
        $contact = $contact[0];

        return Contact::createFromArray($contact);
    }
}
