<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Plugin
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Plugin;

use JsonSerializable;
use MaarchCourrier\Core\Domain\Plugin\Port\PluginInterface;

class Plugin implements PluginInterface, JsonSerializable
{
    public string $id;
    public string $url;
    public array $config = [];

    public function setId(string $id): PluginInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setUrl(string $url): PluginInterface
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setConfig(array $config): PluginInterface
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function updateLicense(string $license): void
    {
        $pluginConfig = $this->getConfig();
        $pluginConfig['license'] = $license;
        $this->setConfig($pluginConfig);
    }

    public function jsonSerialize(): array
    {
        $return = [
            'id'     => $this->id,
            'url'    => $this->url,
            'config' => $this->config
        ];

        if (empty($this->config)) {
            unset($return['config']);
        }

        return $return;
    }
}
