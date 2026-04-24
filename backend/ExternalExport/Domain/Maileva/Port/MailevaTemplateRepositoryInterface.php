<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Template Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Port;

use MaarchCourrier\ExternalExport\Domain\Maileva\MailevaTemplate;

interface MailevaTemplateRepositoryInterface
{
    public function getById(int $id): ?MailevaTemplate;

    /**
     * @param array $ids
     *
     * @return MailevaTemplate[]
     */
    public function getByEntityIds(array $ids): array;
}
