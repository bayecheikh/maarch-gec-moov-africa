<?php

/**
 * Copyright Maarch since 2008 under license.
 * See LICENSE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Workflow Item
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain;

enum SignatureMode: string
{
    case RGS_KEY = 'rgs_2stars';
    case STAMP = 'stamp';
}
