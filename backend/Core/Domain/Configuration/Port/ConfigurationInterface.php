<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Configuration Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Configuration\Port;

interface ConfigurationInterface
{
    public function getId(): int;

    public function setId(int $id): self;

    public function getPrivilege(): string;

    public function setPrivilege(string $privilege): self;

    public function getValue(): array;

    public function setValue(array $value): self;
}
