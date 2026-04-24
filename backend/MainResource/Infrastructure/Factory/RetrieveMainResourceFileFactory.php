<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Main Resource File Factory Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\MainResource\Infrastructure\Factory;

use MaarchCourrier\Core\Domain\MainResource\Port\RetrieveMainResourceFileFactoryInterface;
use Resource\Application\RetrieveDocserverAndFilePath;
use Resource\Application\RetrieveResource;
use Resource\Infrastructure\ResourceData;
use Resource\Infrastructure\ResourceFile;

class RetrieveMainResourceFileFactory implements RetrieveMainResourceFileFactoryInterface
{
    public static function create(): RetrieveResource
    {
        $resourceRepository = new ResourceData();
        $resourceFile = new ResourceFile();

        return new RetrieveResource(
            $resourceRepository,
            $resourceFile,
            new RetrieveDocserverAndFilePath($resourceRepository, $resourceFile)
        );
    }
}
