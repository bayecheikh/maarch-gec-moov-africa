<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief History Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\History\Infrastructure\Repository;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\History\Domain\HistoryRecord;
use MaarchCourrier\History\Domain\Port\HistoryRepositoryInterface;
use SrcCore\models\DatabaseModel;

class HistoryRepository implements HistoryRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function save(HistoryRecord $record, bool $useDatabaseDateTime = true): void
    {
        // 1) Gather all values
        $values = [
            'table_name' => $record->getTableName(),
            'record_id'  => $record->getRecordId(),
            'event_type' => $record->getEventType(),
            'user_id'    => $record->getUser()?->getId(),
            'event_date' => $useDatabaseDateTime ?
                'CURRENT_TIMESTAMP' : $record->getEventDate()->format('Y-m-d H:i:s.u'),
            'info'       => $record->getInfo(),
            'id_module'  => $record->getModuleId(),
            'remote_ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
            'event_id'   => $record->getEventId(),
        ];

        // 2) Filter out NULLs so they become actual SQL NULLs
        $columnsValues = array_filter(
            $values,
            fn($v) => $v !== null
        );

        // 3) Insert
        DatabaseModel::insert([
            'table' => 'history',
            'columnsValues' => $columnsValues,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function doesHistoryRecordExistFromInfoMsgAndEventDate(
        string $info,
        string|DateTimeImmutable $eventDate
    ): bool {
        if ($eventDate instanceof DateTimeImmutable) {
            $eventDate = $eventDate->format('Y-m-d H:i:s.u');
        }

        $result = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['history'],
            'where'  => ['info = ? AND event_date = ?'],
            'data'   => [$info, $eventDate]
        ]);

        return !empty($result);
    }

    /**
     * @throws Exception
     */
    public function doesHistoryRecordExistFromInfoMsgAndEventDateAndRecordId(
        string $info,
        DateTimeImmutable|string $eventDate,
        string $recordId,
        string $tableName
    ): bool {
        if ($eventDate instanceof DateTimeImmutable) {
            $eventDate = $eventDate->format('Y-m-d H:i:s.u');
        }

        $result = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['history'],
            'where'  => ['info = ? AND event_date = ? AND record_id = ? AND table_name = ?'],
            'data'   => [$info, $eventDate, $recordId, $tableName]
        ]);

        return !empty($result);
    }
}
