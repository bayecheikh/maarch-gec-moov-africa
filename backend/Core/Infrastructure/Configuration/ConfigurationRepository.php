<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Configuration Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Infrastructure\Configuration;

use Exception;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;
use MaarchCourrier\Core\Domain\Configuration\Configuration;
use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationInterface;
use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;
use SrcCore\models\DatabaseModel;

class ConfigurationRepository implements ConfigurationRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function getByPrivilege(PrivilegeInterface $privilege): ?ConfigurationInterface
    {
        $configuration = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['configurations'],
            'where'  => ['privilege = ?'],
            'data'   => [$privilege->getName()],
        ]);

        if (empty($configuration[0])) {
            return null;
        }

        return (new Configuration())
            ->setId((int)$configuration[0]['id'])
            ->setPrivilege($privilege->getName())
            ->setValue(json_decode($configuration[0]['value'], true));
    }

    /**
     * @throws Exception
     */
    public function createByPrivilege(PrivilegeInterface $privilege, array $value): void
    {
        DatabaseModel::insert([
            'table'         => 'configurations',
            'columnsValues' => [
                'privilege' => $privilege->getName(),
                'value'     => json_encode($value)
            ]
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateByPrivilege(PrivilegeInterface $privilege, array $value): void
    {
        DatabaseModel::update([
            'table' => 'configurations',
            'set'   => ['value' => json_encode($value)],
            'where' => ['privilege = ?'],
            'data'  => [$privilege->getName()]
        ]);
    }

    /**
     * @param PrivilegeInterface $privilege
     * @return void
     * @throws Exception
     */
    public function deleteByPrivilege(PrivilegeInterface $privilege): void
    {
        DatabaseModel::delete([
            'table' => 'configurations',
            'where' => ['privilege = ?'],
            'data'  => [$privilege->getName()]
        ]);
    }
}
