<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Common Config Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port;

interface IxbusCommonConfigInterface
{
    public function getTokenAPI(): string;
    public function setTokenAPI(string $token): static;
    public function getUrl(): string;
    public function setUrl(string $url): static;
    public function isOptionSendOfficeDocument(): bool;
    public function setOptionSendOfficeDocument(bool $optionSendOfficeDocument): static;
}
