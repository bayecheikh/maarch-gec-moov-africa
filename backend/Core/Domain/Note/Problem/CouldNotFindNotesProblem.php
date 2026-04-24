<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Could Not Find Notes Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Note\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CouldNotFindNotesProblem extends Problem
{
    public function __construct(array $noteIds)
    {
        parent::__construct(
            _NOTES_COMMENT . " " . _NOT_FOUND,
            400,
            $noteIds
        );
    }
}
