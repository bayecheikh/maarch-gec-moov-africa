<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource Contact Mode Enum
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource;

enum ResourceContactMode: string
{
    case SENDER = 'sender';
    case RECIPIENT = 'recipient';
}
