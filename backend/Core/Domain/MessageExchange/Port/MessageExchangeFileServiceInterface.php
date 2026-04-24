<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Message Exchange File Service Interface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MessageExchange\Port;

interface MessageExchangeFileServiceInterface
{
    /**
     * @param int $id
     *
     * @return array An empty array if no file was found, or an array that contains:
     *        - 'path': File content.
     *        - 'name': File name.
     */
    public function getFileNameAndContentById(int $id): array;
}
