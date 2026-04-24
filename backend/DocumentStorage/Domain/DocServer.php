<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief DocServer class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain;

use DateTimeImmutable;

class DocServer
{
    private int $id;
    private string $docserverId;
    private DocServerType $docserverType;
    private ?string $deviceLabel = null;
    private string $isReadonly;
    private int $sizeLimitNumber;
    private int $actualSizeNumber;
    private string $pathTemplate;
    private DateTimeImmutable $creationDate;
    private string $collId;
    private bool $isEncrypted;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDocserverId(): string
    {
        return $this->docserverId;
    }

    public function setDocserverId(string $docserverId): self
    {
        $this->docserverId = $docserverId;
        return $this;
    }

    public function getDocserverType(): DocServerType
    {
        return $this->docserverType;
    }

    public function setDocserverType(DocServerType $docserverType): self
    {
        $this->docserverType = $docserverType;
        return $this;
    }

    public function getDeviceLabel(): ?string
    {
        return $this->deviceLabel;
    }

    public function setDeviceLabel(?string $deviceLabel): self
    {
        $this->deviceLabel = $deviceLabel;
        return $this;
    }

    public function getIsReadonly(): string
    {
        return $this->isReadonly;
    }

    public function setIsReadonly(string $isReadonly): self
    {
        $this->isReadonly = $isReadonly;
        return $this;
    }

    public function getSizeLimitNumber(): int
    {
        return $this->sizeLimitNumber;
    }

    public function setSizeLimitNumber(int $sizeLimitNumber): self
    {
        $this->sizeLimitNumber = $sizeLimitNumber;
        return $this;
    }

    public function getActualSizeNumber(): int
    {
        return $this->actualSizeNumber;
    }

    public function setActualSizeNumber(int $actualSizeNumber): self
    {
        $this->actualSizeNumber = $actualSizeNumber;
        return $this;
    }

    public function getPathTemplate(): string
    {
        return $this->pathTemplate;
    }

    public function setPathTemplate(string $pathTemplate): self
    {
        $this->pathTemplate = $pathTemplate;
        return $this;
    }

    public function getCreationDate(): DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTimeImmutable $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getCollId(): string
    {
        return $this->collId;
    }

    public function setCollId(string $collId): self
    {
        $this->collId = $collId;
        return $this;
    }

    public function getIsEncrypted(): bool
    {
        return $this->isEncrypted;
    }

    public function setIsEncrypted(bool $isEncrypted): self
    {
        $this->isEncrypted = $isEncrypted;
        return $this;
    }
}
