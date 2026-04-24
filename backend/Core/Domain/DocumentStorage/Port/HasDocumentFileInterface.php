<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Has Document File Interface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\DocumentStorage\Port;

interface HasDocumentFileInterface
{
    public function getDocServerId(): ?string;
    public function getPath(): ?string;
    public function getFilename(): ?string;
}
