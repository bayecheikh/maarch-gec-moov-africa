<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief NewInternalParapheurDisabledProblem
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Core\Domain\Problem;

class NewInternalParapheurDisabledProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(_NEW_INTERNAL_SIGNATORY_BOOK_DISABLED, 400);
    }
}
