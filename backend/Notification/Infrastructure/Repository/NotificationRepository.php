<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Repository Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Infrastructure\Repository;

use Exception;
use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;
use MaarchCourrier\Notification\Domain\Notification;
use MaarchCourrier\Notification\Domain\Port\NotificationRepositoryInterface;
use SrcCore\models\DatabaseModel;

class NotificationRepository implements NotificationRepositoryInterface
{
    private function createNotificationFromArray(array $values): NotificationInterface
    {
        $diffusionProperties = [];
        if (!empty($values['diffusion_properties'] ?? null)) {
            $diffusionProperties = explode(',', $values['diffusion_properties']);
        }

        $attachForProperties = [];
        if (!empty($values['attachfor_properties'] ?? null)) {
            $attachForProperties = explode(',', $values['attachfor_properties']);
        }


        return (new Notification())
            ->setId($values['notification_sid'])
            ->setStringId($values['notification_id'])
            ->setDescription($values['description'] ?? null)
            ->setEnabled($values['is_enabled'] == 'Y')
            ->setEventId($values['event_id'])
            ->setMode($values['notification_mode'])
            ->setTemplateId($values['template_id'] ?? null)
            ->setDiffusionType($values['diffusion_type'])
            ->setDiffusionProperties($diffusionProperties)
            ->setAttachForType($values['attachfor_type'] ?? null)
            ->setAttachForProperties($attachForProperties)
            ->setSendAsRecap($values['send_as_recap'] ?? false);
    }

    /**
     * @throws Exception
     */
    public function getByStringId(string $stringId): ?NotificationInterface
    {
        $notif = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['notifications'],
            'where'  => ['notification_id = ?'],
            'data'   => [$stringId]
        ]);

        return !empty($notif[0]) ? $this->createNotificationFromArray($notif[0]) : null;
    }
}
