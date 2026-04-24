<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Invalid Date Format Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Problem;

class InvalidDateFormatProblem extends Problem
{
    public function __construct(string $format, array $availableFormats = [])
    {
        $info = "Invalid date format : '$format'.";
        $context = ['format' => $format];

        if (!empty($availableFormats)) {
            $info .= " Available formats are : " . implode(', ', $availableFormats);
            $context['availableFormats'] = $availableFormats;
        }

        parent::__construct($info, 400, $context, 'invalidDateFormat');
    }
}
