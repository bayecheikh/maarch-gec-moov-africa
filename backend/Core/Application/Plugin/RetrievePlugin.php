<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Plugins class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Application\Plugin;

use MaarchCourrier\Core\Domain\Plugin\Port\PluginInterface;
use MaarchCourrier\Core\Domain\Plugin\Port\PluginsManagerServiceInterface;
use MaarchCourrier\Core\Domain\Plugin\Problem\CouldNotFindPluginProblem;
use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;

class RetrievePlugin
{
    public function __construct(
        public readonly EnvironmentInterface $environment,
        public readonly PluginsManagerServiceInterface $pluginsManagerService,
    ) {
    }

    /**
     * @param string|null $pluginId
     *
     * @return array|PluginInterface
     * @throws CouldNotFindPluginProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     */
    public function get(?string $pluginId = null): array|PluginInterface
    {
        $plugins = $this->pluginsManagerService->fetchPlugins();

        if ($pluginId !== null) {
            $pluginId = trim($pluginId);

            if (empty($pluginId)) {
                throw new ParameterStringCanNotBeEmptyProblem('pluginId');
            }

            $plugin = array_filter($plugins, function (PluginInterface $plugin) use ($pluginId) {
                return $plugin->getId() === $pluginId;
            });
            $plugin = array_shift($plugin);

            if (empty($plugin)) {
                throw new CouldNotFindPluginProblem($pluginId);
            }

            $envName = str_replace('-', '_', $plugin->getId());
            $envName = strtoupper($envName);

            $licenseFromEnv = $this->environment->getPluginLicense("{$envName}_LICENSE");
            if (!empty($licenseFromEnv)) {
                $plugin->updateLicense($licenseFromEnv); //use env license instead
            }

            return $plugin;
        } else {
            // get plugins license from environment vars
            $pluginsLicenseFromEnv = $this->environment->getPluginsLicense();

            foreach ($plugins as $plugin) {
                foreach ($pluginsLicenseFromEnv as $pluginEnvName => $licenseFromEnv) {
                    $pluginNameFromEnv = strtolower(str_replace('_LICENSE', '', $pluginEnvName));
                    $pluginNameFromEnv = str_replace('_', '-', $pluginNameFromEnv);
                    if ($plugin->getId() === $pluginNameFromEnv && !empty($licenseFromEnv)) {
                        $plugin->updateLicense($licenseFromEnv);
                    }
                }
            }

            return $plugins;
        }
    }
}
