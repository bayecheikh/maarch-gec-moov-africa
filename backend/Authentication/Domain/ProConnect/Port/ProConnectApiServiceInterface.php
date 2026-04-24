<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ProConnect Api Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Domain\ProConnect\Port;

interface ProConnectApiServiceInterface
{
    public function setConfig(array $proConnectConfig, string $authenticationCode, string $nonce): void;

    public function generateToken(): void;

    public function isTokenValid(): bool;

    public function getUserInfos(): array;

    public function getIdToken(): ?string;
}
