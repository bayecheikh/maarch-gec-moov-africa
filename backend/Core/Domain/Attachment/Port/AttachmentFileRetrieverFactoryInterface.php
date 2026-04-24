<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment File Retriever Factory Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Attachment\Port;

use MaarchCourrier\Attachment\Application\AttachmentFileRetriever;

interface AttachmentFileRetrieverFactoryInterface
{
    public static function create(): AttachmentFileRetriever;
}
