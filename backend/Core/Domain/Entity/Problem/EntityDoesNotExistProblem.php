<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Entity Does Not Exist Problem
 * @author dev@maarch.org
 */
namespace MaarchCourrier\Core\Domain\Entity\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class EntityDoesNotExistProblem extends Problem
{
    public function __construct(?int $entityId = null)
    {
        $message = empty($entityId) ? _ENTITY . " " . _NOT_EXISTS : _ENTITY . " '$entityId' " . _NOT_EXISTS;
        parent::__construct(
            $message,
            400,
            [
                'userId' => $entityId
            ]
        );
    }
}
