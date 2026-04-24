<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief DocServer Type Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Infrastructure\Repository;

use Exception;
use MaarchCourrier\DocumentStorage\Domain\DocServerType;
use MaarchCourrier\DocumentStorage\Domain\Port\DocServerTypeRepositoryInterface;
use SrcCore\models\DatabaseModel;

class DocServerTypeRepository implements DocServerTypeRepositoryInterface
{
    /**
     * @param string $id
     *
     * @return DocServerType|null
     * @throws Exception
     */
    public function getById(string $id): ?DocServerType
    {
        $type = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['docserver_types'],
            'where'  => ['docserver_type_id = ?'],
            'data'   => [$id]
        ]);

        if (empty($type[0])) {
            return null;
        }

        return (new DocServerType())
            ->setDocserverTypeId($type[0]['docserver_type_id'])
            ->setDocserverTypeLabel($type[0]['docserver_type_label'] ?? null)
            ->setEnabled($type[0]['enabled'] == 'Y')
            ->setFingerprintMode($type[0]['fingerprint_mode'] ?? null);
    }
}
