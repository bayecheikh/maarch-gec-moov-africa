<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Event Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Infrastructure\Repository;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationEventRepositoryInterface;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Notification\Domain\Notification;
use MaarchCourrier\Notification\Domain\NotificationEvent;
use MaarchCourrier\Notification\Domain\Port\NotificationEventInterface;
use SrcCore\models\DatabaseModel;

class NotificationEventRepository implements NotificationEventRepositoryInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * @param array $data
     * @param UserInterface[] $users
     * @return NotificationEventInterface
     * @throws Exception
     */
    private function createEventFromData(array $data, array $users): NotificationEventInterface
    {
        //TODO finish $notification and $user obj
        $notification = (new Notification())->setId($data['notification_sid']);

        $execDate = !empty($data['exec_date']) ? new DateTimeImmutable($data['exec_date']) : null;

        return (new NotificationEvent())
            ->setId($data['event_stack_sid'])
            ->setNotification($notification)
            ->setTableName($data['table_name'])
            ->setRecordId($data['record_id'])
            ->setUser($users[$data['user_id']])
            ->setInfo($data['event_info'])
            ->setDate(new DateTimeImmutable($data['event_date']))
            ->setExecDate($execDate)
            ->setResult($data['exec_result']);
    }

    /**
     * @throws Exception
     */
    public function insertMultiple(array $eventValues): void
    {
        DatabaseModel::insertMultiple([
            'table'   => 'notif_event_stack',
            'columns' => ['table_name', 'notification_sid', 'record_id', 'user_id', 'event_info', 'event_date'],
            'values'  => $eventValues
        ]);
    }

    /**
     * @throws Exception
     */
    public function getRecordByInfoAndUser(string $info, int $userIdToNotify, ?int $redirectedUserId = null): array
    {
        $where = !empty($redirectedUserId) ?
            ['event_info = ?', '(user_id = ? OR user_id = ?)'] : ['event_info = ?', 'user_id = ?'];
        $data = !empty($redirectedUserId) ? [$info, $userIdToNotify, $redirectedUserId] : [$info, $userIdToNotify];

        $events = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['notif_event_stack'],
            'where'  => $where,
            'data'   => $data
        ]);

        $userIds = array_unique(array_filter(array_column($events, 'user_id')));
        $users = $this->userRepository->getUsersByIds($userIds);

        return array_map(fn(array $item) => $this->createEventFromData($item, $users), $events);
    }

    /**
     * @return NotificationEventInterface[]
     * @throws Exception
     */
    public function getPendingEventsByNotification(NotificationInterface $notification): array
    {
        $events = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['notif_event_stack'],
            'where'  => ['notification_sid = ?', 'exec_date is NULL'],
            'data'   => [$notification->getId()]
        ]);

        $userIds = array_unique(array_filter(array_column($events, 'user_id')));
        $users = $this->userRepository->getUsersByIds($userIds);

        return array_map(fn(array $item) => $this->createEventFromData($item, $users), $events);
    }

    /**
     * @throws Exception
     */
    public function setExecResultForIds(string $result, array $ids): void
    {
        DatabaseModel::update([
            'table' => 'notif_event_stack',
            'set'   => ['exec_date' => 'CURRENT_TIMESTAMP', 'exec_result' => $result],
            'where' => ['event_stack_sid IN (?)'],
            'data'  => [$ids]
        ]);
    }
}
