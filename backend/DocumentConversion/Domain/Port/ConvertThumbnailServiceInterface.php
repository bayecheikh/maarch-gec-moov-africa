<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Convert Image Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentConversion\Domain\Port;

interface ConvertThumbnailServiceInterface
{
    public function convertOnePageFromFileContent(string $fileContent, string $fileFormat, int $page): array;
}
