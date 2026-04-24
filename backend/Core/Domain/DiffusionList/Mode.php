<?php

/**
 * Copyright Maarch since 2008 under license.
 * See LICENSE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Mode Enum
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\DiffusionList;

enum Mode: string
{
    case DEST = 'dest';
    case COPY = 'cc';
    case VISA = 'visa';
    case SIGN = 'sign';
    case AVIS = 'avis';
    case AVIS_CC = 'avis_copy';
    case AVIS_INFO = 'avis_info';
}
