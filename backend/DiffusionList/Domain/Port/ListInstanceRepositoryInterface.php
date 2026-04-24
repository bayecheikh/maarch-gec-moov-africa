<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ListInstanceRepositoryInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DiffusionList\Domain\Port;

use MaarchCourrier\DiffusionList\Domain\ListInstance;

interface ListInstanceRepositoryInterface
{
    /**
     * @param int $resId
     * @return ListInstance
     */
    public function getNextInCircuit(int $resId): ListInstance;

    /**
     * @param array $args
     * @return ListInstance[]
     */
    public function getListInstanceCircuit(array $args): array;

    public function updateListInstance(ListInstance $listInstance, array $set): void;

    public function getListInstanceByResIdAndType(array $args): array;

    public function replaceListInstanceByResIdAndType(int $resId, string $type, array $listInstances): void;

    public function getUsersInDestFromDistributionToServices(): array;

    /**
     * @return ListInstance[]
     */
    public function getEntitiesInCopyFromDistributionToServices(): array;
}
