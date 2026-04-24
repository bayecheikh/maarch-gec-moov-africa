<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Configuration Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Configuration;

use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private int $id;
    private string $privilege;
    private array $value;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ConfigurationInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getPrivilege(): string
    {
        return $this->privilege;
    }

    public function setPrivilege(string $privilege): ConfigurationInterface
    {
        $this->privilege = $privilege;
        return $this;
    }

    public function getValue(): array
    {
        return $this->value;
    }

    public function setValue(array $value): ConfigurationInterface
    {
        $this->value = $value;
        return $this;
    }
}
