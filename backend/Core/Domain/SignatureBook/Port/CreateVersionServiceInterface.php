<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CreateVersionServiceInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\SignatureBook\Port;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;

interface CreateVersionServiceInterface
{
    public function createVersionForResource(MainResourceInterface $mainResource, array $infos): array;
    public function createSignedVersionForResource(MainResourceInterface $mainResource, array $infos): void;
    public function createVersionForAttachment(AttachmentInterface $attachment, array $infos): array;
}
