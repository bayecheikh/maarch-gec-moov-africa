<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief External Signature Book Type
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain;

enum ExternalSignatureBookType: string
{
    case IXBUS = 'ixbus';
    case FAST = 'fastParapheur';
    case GOODFLAG = 'goodflag';
}
