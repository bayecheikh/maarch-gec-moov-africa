<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Original Main Resource File Factory Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\MainResource\Infrastructure\Factory;

use MaarchCourrier\Core\Domain\MainResource\Port\RetrieveOriginalMainResourceFileFactoryInterface;
use Resource\Application\RetrieveDocserverAndFilePath;
use Resource\Application\RetrieveOriginalResource;
use Resource\Infrastructure\ResourceData;
use Resource\Infrastructure\ResourceFile;

class RetrieveOriginalMainResourceFileFactory implements RetrieveOriginalMainResourceFileFactoryInterface
{
    public static function create(): RetrieveOriginalResource
    {
        $resourceRepository = new ResourceData();
        $resourceFile = new ResourceFile();

        return new RetrieveOriginalResource(
            $resourceRepository,
            $resourceFile,
            new RetrieveDocserverAndFilePath($resourceRepository, $resourceFile)
        );
    }
}
