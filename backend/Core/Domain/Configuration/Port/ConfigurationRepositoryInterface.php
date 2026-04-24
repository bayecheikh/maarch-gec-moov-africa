<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Configuration Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Configuration\Port;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;

interface ConfigurationRepositoryInterface
{
    public function getByPrivilege(PrivilegeInterface $privilege): ?ConfigurationInterface;
    public function createByPrivilege(PrivilegeInterface $privilege, array $value): void;
    public function updateByPrivilege(PrivilegeInterface $privilege, array $value): void;
    public function deleteByPrivilege(PrivilegeInterface $privilege): void;
}
