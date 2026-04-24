<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Environment class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Infrastructure;

use Exception;
use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use SrcCore\models\CoreConfigModel;

class Environment implements EnvironmentInterface
{
    /**
     * @return bool
     * @throws Exception
     */
    public function isDebug(): bool
    {
        $file = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
        $config = $file['config'];
        return !empty($config['debug']);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isNewInternalParapheurEnabled(): bool
    {
        $file = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
        $config = $file['config'];
        return !empty($config['newInternalParaph']);
    }


    public function getTmpDir(): string
    {
        return CoreConfigModel::getTmpPath();
    }

    public function getPluginLicense(string $pluginEnvName): ?string
    {
        return $_ENV[$pluginEnvName] ?? $_SERVER[$pluginEnvName] ?? null;
    }

    public function getPluginsLicense(): array
    {
        // Loop through $_ENV and filter keys that match 'MAARCH_PLUGINS_*_LICENSE'
        $envArray = array_filter($_ENV, function ($key) {
            return preg_match('/^MAARCH_PLUGINS_.*_LICENSE$/', $key);
        }, ARRAY_FILTER_USE_KEY);
        // Loop through $_SERVER and filter keys that match 'MAARCH_PLUGINS_*_LICENSE'
        $serverArray = array_filter($_SERVER, function ($key) {
            return preg_match('/^MAARCH_PLUGINS_.*_LICENSE$/', $key);
        }, ARRAY_FILTER_USE_KEY);
        // Avoid duplicates
        return $envArray + $serverArray;
    }

    public function getServerVariables(): array
    {
        return $_SERVER;
    }

    public function getGlobalVariables(): array
    {
        return $GLOBALS;
    }

    public function setGlobalVariable(string $key, string|int $value): void
    {
        $GLOBALS[$key] = $value;
    }

    /**
     * @return string Return the logging method id from the login_method.xml. If not found or all methods are disabled,
     *     return 'standard'
     * @throws Exception
     */
    public function getLoggingMethod(): string
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'config/login_method.xml']);

        $loggingMethodId = 'standard';
        if (!empty($loadedXml)) {
            foreach ($loadedXml->METHOD as $value) {
                if ((string)$value->ENABLED == 'true') {
                    $loggingMethodId = (string)$value->ID;
                }
            }
        }

        return $loggingMethodId;
    }

    /**
     * @throws Exception
     */
    public function isLegacyConversionEnabled(): bool
    {
        $file = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
        $config = $file['config'];
        return $config['isLegacyConversionEnabled'] ?? true;
    }

    /**
     * Database Unique Id Function
     */
    public function getUniqueId(): string
    {
        return CoreConfigModel::uniqueId();
    }

    /**
     * @throws Exception
     */
    public function getLanguageType(): string
    {
        return CoreConfigModel::getLanguage();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isPdfNormalizationEnabled(): bool
    {
        $file = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
        $config = $file['config'];
        return $config['normalizePdf'] ?? true;
    }
}
