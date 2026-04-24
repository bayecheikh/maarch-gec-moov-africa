<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource Contact Type Enum
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource;

enum ResourceContactType: string
{
    case USER = 'user';
    case CONTACT = 'contact';
    case ENTITY = 'entity';
}
