<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Plugin Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Plugin\Port;

interface PluginInterface
{
    public function setId(string $id): self;
    public function getId(): string;
    public function setUrl(string $url): self;
    public function getUrl(): string;
    public function setConfig(array $config): self;
    public function getConfig(): array;
    public function updateLicense(string $license): void;
}
