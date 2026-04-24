<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Workflow Item Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port;

interface GoodflagWorkflowItemInterface
{
    public function getId(): ?string;

    public function setId(?string $id): self;

    public function getFirstname(): ?string;

    public function setFirstname(?string $firstname): self;

    public function getLastname(): ?string;

    public function setLastname(?string $lastname): self;

    public function getEmail(): string;

    public function setEmail(string $email): self;

    public function getPhone(): ?string;

    public function setPhone(?string $phone): self;

    public function getCountry(): ?string;

    public function setCountry(?string $country): self;

    public function getConsentPageId(): ?string;

    public function setConsentPageId(?string $consentPageId): self;

    public function isRequired(): bool;

    public function setRequired(bool $isRequired): self;
}
