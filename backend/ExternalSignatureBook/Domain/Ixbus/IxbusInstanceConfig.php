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

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus;

use JsonSerializable;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port\IxbusInstanceConfigInterface;

class IxbusInstanceConfig implements IxbusInstanceConfigInterface, JsonSerializable
{
    private string $id;
    private string $label;
    private string $tokenAPI;
    private string $url;
    private bool $optionSendOfficeDocument;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): IxbusInstanceConfigInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): IxbusInstanceConfigInterface
    {
        $this->label = $label;
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

    public function jsonSerialize(): array
    {
        return [
            'id'                        => $this->getId(),
            'label'                     => $this->getLabel(),
            'tokenAPI'                  => $this->getTokenAPI(),
            'url'                       => $this->getUrl(),
            'optionsSendOfficeDocument' => $this->isOptionSendOfficeDocument()
        ];
    }
}
