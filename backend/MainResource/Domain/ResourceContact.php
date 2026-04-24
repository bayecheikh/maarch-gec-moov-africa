<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource Contact
 * @author dev@maarch.org
 */

namespace MaarchCourrier\MainResource\Domain;

use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\ResourceContactInterface;
use MaarchCourrier\Core\Domain\MainResource\ResourceContactMode;
use MaarchCourrier\Core\Domain\MainResource\ResourceContactType;

class ResourceContact implements ResourceContactInterface
{
    private int $id;
    private MainResourceInterface $mainResource;
    private ContactInterface|int $item;
    private ResourceContactType $type;
    private ResourceContactMode $mode;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ResourceContactInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getMainResource(): MainResourceInterface
    {
        return $this->mainResource;
    }

    public function setMainResource(MainResourceInterface $resource): ResourceContactInterface
    {
        $this->mainResource = $resource;
        return $this;
    }

    public function getItem(): ContactInterface|int
    {
        return $this->item;
    }

    public function setItem(ContactInterface|int $item): ResourceContactInterface
    {
        $this->item = $item;
        return $this;
    }

    public function getType(): ResourceContactType
    {
        return $this->type;
    }

    public function setType(ResourceContactType $resource): ResourceContactInterface
    {
        $this->type = $resource;
        return $this;
    }

    public function getMode(): ResourceContactMode
    {
        return $this->mode;
    }

    public function setMode(ResourceContactMode $resource): ResourceContactInterface
    {
        $this->mode = $resource;
        return $this;
    }
}
