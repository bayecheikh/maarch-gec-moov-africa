<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Instance Config Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port;

interface IxbusInstanceConfigInterface extends IxbusCommonConfigInterface
{
    public function getId(): string;
    public function setId(string $id): self;
    public function getLabel(): string;
    public function setLabel(string $label): self;
}
