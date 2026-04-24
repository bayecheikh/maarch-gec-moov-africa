<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Instance Configuration class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag;

use JsonSerializable;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagInstanceConfigInterface;

class GoodflagInstanceConfig implements GoodflagInstanceConfigInterface, JsonSerializable
{
    private string $id;
    private string $label;
    private string $description;
    private string $signatureProfileId;
    private string $consentPageId;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSignatureProfileId(): string
    {
        return $this->signatureProfileId;
    }

    public function setSignatureProfileId(string $signatureProfileId): self
    {
        $this->signatureProfileId = $signatureProfileId;
        return $this;
    }

    public function getConsentPageId(): string
    {
        return $this->consentPageId;
    }

    public function setConsentPageId(string $consentPageId): self
    {
        $this->consentPageId = $consentPageId;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'                 => $this->getId(),
            'label'              => $this->getLabel(),
            'description'        => $this->getDescription(),
            'signatureProfileId' => $this->getSignatureProfileId(),
            'consentPageId'      => $this->getConsentPageId()
        ];
    }
}
