<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Thumbnail Of Content By Page
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class FileTypeIsNotAllowedProblem extends Problem
{
    public function __construct(string $mimeType)
    {
        parent::__construct(
            _FILE_TYPE_NOT_ALLOWED . " : $mimeType",
            400,
            [
                'fileMimeType' => $mimeType
            ],
            'thisFileTypeIsNotAllowed'
        );
    }
}
