<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief EnvironnementInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Port;

interface EnvironmentInterface
{
    public function isDebug(): bool;

    public function isNewInternalParapheurEnabled(): bool;

    public function getTmpDir(): string;

    public function getPluginLicense(string $pluginEnvName): ?string;

    public function getPluginsLicense(): array;

    public function getServerVariables(): array;

    public function getGlobalVariables(): array;

    public function setGlobalVariable(string $key, string|int $value): void;

    public function getLoggingMethod(): string;

    public function isLegacyConversionEnabled(): bool;

    public function getUniqueId(): string;

    public function getLanguageType(): string;

    public function isPdfNormalizationEnabled(): bool;
}
