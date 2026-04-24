<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Has Docserver File Interface
 * @author dev@maarch.org
 */

namespace Resource\Domain;

use MaarchCourrier\Core\Domain\DocumentStorage\Port\HasDocumentFileInterface;

/**
 * @deprecated see HasDocumentFileInterface below
 * @see HasDocumentFileInterface
 */
interface HasDocserverFileInterface
{
    public function getDocserverId(): ?string;
    public function getPath(): ?string;
    public function getFilename(): ?string;
}
