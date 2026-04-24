<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Could Not Get Message Exchange File Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MessageExchange\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class CouldNotGetMessageExchangeFileProblem extends Problem
{
    public function __construct(string $details)
    {
        parent::__construct(
            _GET_MESSAGE_EXCHANGE_FAILED . ":: $details",
            400
        );
    }
}
