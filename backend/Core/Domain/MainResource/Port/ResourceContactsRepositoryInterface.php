<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource Contacts Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource\Port;

interface ResourceContactsRepositoryInterface
{
    /**
     * @param MainResourceInterface $resource
     *
     * @return ResourceContactInterface[]
     */
    public function getResourceContactsFromMainResource(MainResourceInterface $resource): array;

    /**
     * @param MainResourceInterface $resource
     *
     * @return ResourceContactInterface[]
     */
    public function getRecipientContactsFromMainResource(MainResourceInterface $resource): array;
}
