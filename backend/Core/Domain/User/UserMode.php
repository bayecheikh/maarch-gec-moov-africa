<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Role Enum
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User;

enum UserMode: string
{
    case STANDARD = 'standard';
    case REST = 'rest';
    case VISIBLE_ROOT = 'root_visible';
    case INVISIBLE_ROOT = 'root_invisible';
}
