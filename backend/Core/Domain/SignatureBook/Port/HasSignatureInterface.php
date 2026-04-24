<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief HasSignatureInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\SignatureBook\Port;

interface HasSignatureInterface
{
    public function getHasStampSignature(): ?bool;
    public function getHasDigitalSignature(): ?bool;
}
