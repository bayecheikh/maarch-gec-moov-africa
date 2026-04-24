<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Api Service Interface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Port;

use MaarchCourrier\Core\Domain\Port\CurlErrorInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port\IxbusCommonConfigInterface;

interface IxbusApiServiceInterface
{
    public function getNatures(IxbusCommonConfigInterface $config): CurlErrorInterface|array;
    public function getNatureById(
        IxbusCommonConfigInterface $config,
        string $id
    ): CurlErrorInterface|array;
    public function getEditorUsersFromNatureById(
        IxbusCommonConfigInterface $config,
        string $id
    ): CurlErrorInterface|array;
}
