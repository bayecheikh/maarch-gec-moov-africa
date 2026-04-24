<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief History Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\History\Domain\Port;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\History\Domain\HistoryRecord;

interface HistoryRepositoryInterface
{
    public function save(HistoryRecord $record, bool $useDatabaseDateTime = true): void;

    /**
     * Check If the record exist
     *
     * @param string $info History message
     * @param string|DateTimeImmutable $eventDate The string format needs to be in Y-m-d H:i:s.u
     * @return bool
     * @throws Exception
     */
    public function doesHistoryRecordExistFromInfoMsgAndEventDate(
        string $info,
        string|DateTimeImmutable $eventDate
    ): bool;

    public function doesHistoryRecordExistFromInfoMsgAndEventDateAndRecordId(
        string $info,
        string|DateTimeImmutable $eventDate,
        string $recordId,
        string $tableName
    ): bool;
}
