<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment Not Found Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Attachment;

use MaarchCourrier\Core\Domain\Problem\Problem;

class AttachmentNotFoundProblem extends Problem
{
    public function __construct(int $id)
    {
        parent::__construct(
            _ATTACHMENT_NOT_FOUND . " : " . $id,
            400,
            ['id' => $id]
        );
    }
}
