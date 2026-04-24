<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Config Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port;

interface IxbusConfigInterface extends IxbusCommonConfigInterface
{
    public function getId(): string;
    public function setId(string $id): self;
    /**
     * New config from instances
     * @return bool
     */
    public function isNewConfig(): bool;
    /**
     * @return ?IxbusInstanceConfigInterface[]
     */
    public function getInstances(): ?array;
    /**
     * @param ?IxbusInstanceConfigInterface[] $instances
     *
     * @return self
     */
    public function setInstances(?array $instances): self;
}
