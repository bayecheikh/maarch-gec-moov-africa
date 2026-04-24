<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Group ExternalId Not Found Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GroupExternalIdNotFoundProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _GROUP_NOT_SYNCHRONIZED_WITH_SIGNATORY_BOOK,
            500,
            lang: "userGroupNotSynchronizedWithSignatureBook"
        );
    }
}
