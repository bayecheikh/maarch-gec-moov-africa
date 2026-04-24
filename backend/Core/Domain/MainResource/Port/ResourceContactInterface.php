<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource Contact Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource\Port;

use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\MainResource\ResourceContactMode;
use MaarchCourrier\Core\Domain\MainResource\ResourceContactType;

interface ResourceContactInterface
{
    public function getId(): int;

    public function setId(int $id): self;

    public function getMainResource(): MainResourceInterface;

    public function setMainResource(MainResourceInterface $resource): self;

    /**
     * TODO: Finish refactor when all interfaces : user, contact and entity.
     */
    public function getItem(): ContactInterface|int;

    /**
     * TODO: Finish refactor when all interfaces : user, contact and entity.
     */
    public function setItem(ContactInterface|int $item): self;

    public function getType(): ResourceContactType;

    public function setType(ResourceContactType $resource): self;

    public function getMode(): ResourceContactMode;

    public function setMode(ResourceContactMode $resource): self;
}
