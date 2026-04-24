<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Config Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port;

interface GoodflagConfigInterface
{
    public function setIsEnabled(bool $isEnabled): self;

    public function isEnabled(): bool;

    public function setUrl(string $url): self;

    public function getUrl(): string;

    public function setAccessToken(string $accessToken): self;

    public function getAccessToken(): string;

    public function setTenantId(?string $tenantId): self;

    public function getTenantId(): ?string;

    public function setUsrId(string $usrId): self;

    public function getUsrId(): ?string;

    public function setGroupId(string $groupId): self;

    public function getGroupId(): ?string;

    public function getOptions(): array;

    public function setOptions(array $options): self;
}
