<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Add Substitute In Signatory Book Factory Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\SignatureBook\Port;

use MaarchCourrier\SignatureBook\Application\User\AddSubstituteInSignatoryBook;

interface AddSubstituteInSignatoryBookFactoryInterface
{
    public function create(): AddSubstituteInSignatoryBook;
}
