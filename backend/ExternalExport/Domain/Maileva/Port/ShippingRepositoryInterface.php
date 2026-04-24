<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Shipping Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Port;

use MaarchCourrier\ExternalExport\Domain\Maileva\Shipping;

interface ShippingRepositoryInterface
{
    public function create(Shipping $shipping): void;

    public function getMailevaShippingInfoByResId(int $resId): array;

    public function getMailevaShippingBySendingId(string $sendingId): ?Shipping;
}
