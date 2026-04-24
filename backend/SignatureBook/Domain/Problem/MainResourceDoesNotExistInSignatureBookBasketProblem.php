<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Does Not Exist In Signature Book Basket Problem
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class MainResourceDoesNotExistInSignatureBookBasketProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(_MAIN_RESOURCE_DONT_EXIST_IN_SIGNATORY_BOOK, 400);
    }
}
