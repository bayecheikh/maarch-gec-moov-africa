<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Book Link Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port\Link;

use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;

interface SignatureBookLinkServiceInterface
{
    /**
     * @param SignatureBookResource $signatureBookResource
     *
     * @return void
     */
    public function unlinkResources(SignatureBookResource $signatureBookResource): void;
}
