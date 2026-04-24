<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Email Repository Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Infrastructure\Repository;

use Exception;
use MaarchCourrier\Notification\Domain\NotificationEmail;
use MaarchCourrier\Notification\Domain\Port\NotificationEmailInterface;
use MaarchCourrier\Notification\Domain\Port\NotificationEmailRepositoryInterface;
use SrcCore\models\DatabaseModel;

class NotificationEmailRepository implements NotificationEmailRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function insert(NotificationEmailInterface $notificationEmail): void
    {
        DatabaseModel::insert([
            'table'         => 'notif_email_stack',
            'columnsValues' => [
                'reply_to'      => $notificationEmail->getReplyTo(),
                'recipient'     => $notificationEmail->getRecipient(),
                'cc'            => $notificationEmail->getCc(),
                'bcc'           => $notificationEmail->getBcc(),
                'subject'       => $notificationEmail->getSubject(),
                'html_body'     => $notificationEmail->getBody(),
                'attachments'   => !empty($notificationEmail->getAttachments()) ?
                    implode(',', $notificationEmail->getAttachments()) : null
            ]
        ]);
    }

    /**
     * @return NotificationEmailInterface[]
     * @throws Exception
     */
    public function getUnExecutedEmails(): array
    {
        $notif = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['notif_email_stack'],
            'where'  => ['exec_date is NULL']
        ]);

        if (empty($notif)) {
            return [];
        }

        $notifications = [];
        foreach ($notif as $value) {
            $notifications[] = (new NotificationEmail())
                ->setId($value['email_stack_sid'])
                ->setReplyTo($value['reply_to'] ?? null)
                ->setRecipient($value['recipient'])
                ->setCc($value['cc'] ?? null)
                ->setBcc($value['bcc'] ?? null)
                ->setSubject($value['subject'] ?? null)
                ->setBody($value['html_body'] ?? null)
                ->setAttachments(
                    !empty($value['attachments'] ?? null) ? explode(',', $value['attachments']) : null
                )
                ->setExecutionDate($value['exec_date'] ?? null)
                ->setExecutionStatus($value['exec_result'] ?? null);
        }

        return $notifications;
    }

    /**
     * @throws Exception
     */
    public function setExecutionDate(NotificationEmailInterface $email, ?string $status = null): void
    {
        $set = ['exec_date' => 'CURRENT_TIMESTAMP'];
        if (!empty($status)) {
            $set['exec_result'] = $status;
        }
        DatabaseModel::update([
            'table'   => 'notif_email_stack',
            'set'     => $set,
            'where'   => ['email_stack_sid = ?'],
            'data'    => [$email->getId()],
        ]);
    }
}
