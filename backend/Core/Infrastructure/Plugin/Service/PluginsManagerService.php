<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Plugins Manager Service Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Infrastructure\Plugin\Service;

use Exception;
use MaarchCourrier\Core\Domain\Plugin\Plugin;
use MaarchCourrier\Core\Domain\Plugin\Port\PluginInterface;
use MaarchCourrier\Core\Domain\Plugin\Port\PluginsManagerServiceInterface;
use MaarchCourrier\Core\Domain\Problem\CouldNotLoadJsonFileProblem;
use SrcCore\models\CoreConfigModel;

class PluginsManagerService implements PluginsManagerServiceInterface
{
    /**
     * @return PluginInterface[]
     * @throws CouldNotLoadJsonFileProblem
     * @throws Exception
     */
    public function fetchPlugins(): array
    {
        $configPath = CoreConfigModel::getConfigPath();
        $config = CoreConfigModel::getJsonLoaded(['path' => $configPath]);
        if (empty($config)) {
            throw new CouldNotLoadJsonFileProblem($configPath);
        }

        /**
         * @var PluginInterface[] $plugins
         */
        $plugins = [];
        if (isset($config['config']['plugins']) && is_array($config['config']['plugins'])) {
            foreach ($config['config']['plugins'] as $plugin) {
                if (!empty($plugin['id'] ?? null) && !empty($plugin['url'] ?? null)) {
                    $plugins[] = (new Plugin())
                        ->setId($plugin['id'])
                        ->setUrl($plugin['url'])
                        ->setConfig($plugin['config'] ?? []);
                }
            }
        }

        return $plugins;
    }
}
