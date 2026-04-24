<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Configuration class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag;

use JsonSerializable;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagConfigInterface;

class GoodflagConfig implements GoodflagConfigInterface, JsonSerializable
{
    private bool $isEnabled = false;
    private string $url;
    private string $accessToken;
    private ?string $tenantId = null;
    private ?string $usrId = null;
    private ?string $groupId = null;
    private array $options = [];

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setTenantId(?string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function setUsrId(string $usrId): self
    {
        $this->usrId = $usrId;
        return $this;
    }

    public function getUsrId(): ?string
    {
        return $this->usrId;
    }

    public function setGroupId(string $groupId): self
    {
        $this->groupId = $groupId;
        return $this;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'url'         => $this->getUrl(),
            'accessToken' => $this->getAccessToken(),
            'usrId'       => $this->getUsrId(),
            'groupId'     => $this->getGroupId(),
            'options'     => $this->getOptions()
        ];
    }
}
