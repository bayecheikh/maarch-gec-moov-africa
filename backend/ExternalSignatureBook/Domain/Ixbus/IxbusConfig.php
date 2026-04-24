<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Configuration class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus;

use JsonSerializable;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port\IxbusConfigInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port\IxbusInstanceConfigInterface;

class IxbusConfig implements IxbusConfigInterface, JsonSerializable
{
    private string $id;
    private string $tokenAPI = '';
    private string $url = '';
    private bool $optionSendOfficeDocument = false;
    /**
     * @var ?IxbusInstanceConfigInterface[] $instances
     */
    private ?array $instances = null;


    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): IxbusConfigInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getTokenAPI(): string
    {
        return $this->tokenAPI;
    }

    public function setTokenAPI(string $token): static
    {
        $this->tokenAPI = $token;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }
    public function isOptionSendOfficeDocument(): bool
    {
        return $this->optionSendOfficeDocument;
    }

    public function setOptionSendOfficeDocument(bool $optionSendOfficeDocument): static
    {
        $this->optionSendOfficeDocument = $optionSendOfficeDocument;
        return $this;
    }

    public function isNewConfig(): bool
    {
        return empty($this->tokenAPI) &&
            empty($this->url) &&
            empty($this->optionSendOfficeDocument) &&
            !empty($this->instances);
    }

    public function getInstances(): ?array
    {
        return $this->instances;
    }

    public function setInstances(?array $instances): IxbusConfigInterface
    {
        $this->instances = $instances;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'instances' => $this->getInstances()
        ];
    }
}
