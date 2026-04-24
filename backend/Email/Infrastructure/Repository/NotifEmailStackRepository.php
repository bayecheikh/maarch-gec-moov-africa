<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notif Email Stack Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Infrastructure\Repository;

use Exception;
use MaarchCourrier\Email\Domain\NotifEmailStack;
use SrcCore\models\DatabaseModel;

class NotifEmailStackRepository
{
    /**
     * @return NotifEmailStack[]
     * @throws Exception
     */
    public function getNotifThatWereNotSent(): array
    {
        $notifs = DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['notif_email_stack'],
            'where'     => ['exec_date is NULL']
        ]);

        $array = [];
        foreach ($notifs as $notif) {
            $array[] = (new NotifEmailStack())
                ->setEmailStackSid($notif['email_stack_sid'])
                ->setReplyTo($notif['reply_to'])
                ->setRecipient($notif['recipient'])
                ->setCc($notif['cc'])
                ->setBcc($notif['bcc'])
                ->setSubject($notif['subject'])
                ->setHtmlBody($notif['html_body'])
                ->setAttachments($notif['attachments'])
                ->setExecDate($notif['exec_date'])
                ->setExecResult($notif['exec_result']);
        }

        return $array;
    }
}
