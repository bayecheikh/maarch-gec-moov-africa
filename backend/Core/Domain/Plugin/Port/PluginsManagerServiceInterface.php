<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Plugins Manager Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Plugin\Port;

interface PluginsManagerServiceInterface
{
    /**
     * @return PluginInterface[]
     */
    public function fetchPlugins(): array;
}
