<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Contact\Port;

use MaarchCourrier\Core\Domain\Contact\Problem\PrimaryEntityAddressOfCurrentUserIsNotFilledEnoughProblem;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;

interface AfnorContactServiceInterface
{
    public function getAfnorByContact(ContactInterface $contact): array;

    /**
     * @param CurrentUserInterface $currentUser
     * @throws PrimaryEntityAddressOfCurrentUserIsNotFilledEnoughProblem
     */
    public function getAfnorByCurrentUserPrimaryEntity(CurrentUserInterface $currentUser): array;
}
