<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email Status Enum
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Domain;

enum EmailStatus: string
{
    case DRAFT = 'DRAFT';
    case WAITING = 'WAITING';
    case EXPRESS = 'EXPRESS';
    case SENT = 'SENT';
    case ERROR = 'ERROR';
}
