<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource Contacts Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\MainResource\Infrastructure\Repository;

use Contact\models\ContactModel;
use Exception;
use MaarchCourrier\Contact\Domain\Contact;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\ResourceContactInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\ResourceContactsRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\ResourceContactMode;
use MaarchCourrier\Core\Domain\MainResource\ResourceContactType;
use MaarchCourrier\MainResource\Domain\ResourceContact;
use Resource\models\ResourceContactModel;

class ResourceContactsRepository implements ResourceContactsRepositoryInterface
{
    /**
     * @param MainResourceInterface $resource
     *
     * @return ResourceContactInterface[]
     * @throws Exception
     */
    public function getResourceContactsFromMainResource(MainResourceInterface $resource): array
    {
        $resourceContacts = ResourceContactModel::get([
            'where' => ['res_id = ?'],
            'data'  => [$resource->getResId()]
        ]);

        if (empty($resourceContacts)) {
            return [];
        }

        $list = [];
        foreach ($resourceContacts as $resourceContact) {
            $type = ResourceContactType::from($resourceContact['type']);
            $item = $resourceContact['item_id'];

            if ($type === ResourceContactType::CONTACT) {
                $contact = ContactModel::getById(['select' => ['*'], 'id' => $resourceContact['item_id']]);
                $item = Contact::createFromArray($contact);
            }

            $list[] = (new ResourceContact())
                ->setId($resourceContact['id'])
                ->setMainResource($resource)
                ->setItem($item)
                ->setMode(ResourceContactMode::from($resourceContact['mode']))
                ->setType($type);
        }

        return $list;
    }

    /**
     * @param MainResourceInterface $resource
     *
     * @return ResourceContactInterface[]
     * @throws Exception
     */
    public function getRecipientContactsFromMainResource(MainResourceInterface $resource): array
    {
        $list = $this->getResourceContactsFromMainResource($resource);
        return array_filter($list, function (ResourceContactInterface $resourceContact) {
            return $resourceContact->getMode() === ResourceContactMode::RECIPIENT
                && $resourceContact->getType() === ResourceContactType::CONTACT;
        });
    }
}
