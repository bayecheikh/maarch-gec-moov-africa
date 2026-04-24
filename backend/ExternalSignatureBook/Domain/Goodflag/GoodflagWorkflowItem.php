<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Workflow Item class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag;

use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagWorkflowItemInterface;

class GoodflagWorkflowItem implements GoodflagWorkflowItemInterface
{
    private ?string $id;
    private ?string $firstname;
    private ?string $lastname;
    private string $email;
    private ?string $phone;
    private ?string $country;
    private ?string $consentPageId;
    private bool $isRequired = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getConsentPageId(): ?string
    {
        return $this->consentPageId;
    }

    public function setConsentPageId(?string $consentPageId): self
    {
        $this->consentPageId = $consentPageId;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setRequired(bool $isRequired): self
    {
        $this->isRequired = $isRequired;
        return $this;
    }
}
