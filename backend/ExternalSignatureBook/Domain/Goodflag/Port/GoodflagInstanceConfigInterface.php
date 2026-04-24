<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Instance Config Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port;

interface GoodflagInstanceConfigInterface
{
    public function getId(): string;

    public function setId(string $id): self;

    public function getLabel(): string;

    public function setLabel(string $label): self;

    public function getDescription(): string;

    public function setDescription(string $description): self;

    public function getSignatureProfileId(): string;

    public function setSignatureProfileId(string $signatureProfileId): self;

    public function getConsentPageId(): string;

    public function setConsentPageId(string $consentPageId): self;
}
