<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Item Type Enum
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\DiffusionList;

enum ItemType: string
{
    case USER = 'user_id';
    case ENTITY = 'entity_id';
}
