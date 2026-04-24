<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Shipping Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Infrastructure\Maileva\Repository;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\MailevaTemplateRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\ShippingRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Shipping;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use SrcCore\models\DatabaseModel;

class ShippingRepository implements ShippingRepositoryInterface
{
    public function __construct(
        private readonly MailevaTemplateRepositoryInterface $mailevaTemplateRepository
    ) {
    }

    /**
     * @param Shipping $shipping
     *
     * @return void
     * @throws Exception
     */
    public function create(Shipping $shipping): void
    {
        DatabaseModel::insert([
            'table'         => 'shippings',
            'columnsValues' => [
                'user_id'             => $shipping->getUser()->getId(),
                'sending_id'          => $shipping->getSendingId(),
                'document_id'         => $shipping->getResource()->getResId(),
                'document_type'       => $shipping->getResourceType(),
                'options'             => json_encode($shipping->getOptions()),
                'fee'                 => $shipping->getFee(),
                'recipient_entity_id' => $shipping->getRecipientEntity()->getId(),
                'recipients'          => json_encode($shipping->getRecipients()),
                'account_id'          => $shipping->getAccountId(),
                'creation_date'       => 'CURRENT_TIMESTAMP',
                'action_id'           => $shipping->getActionId(),
                'maileva_template_id' => $shipping->getMailevaTemplate()->getId() ?? null
            ]
        ]);
    }

    /**
     * @throws Exception
     */
    public function getMailevaShippingInfoByResId(int $resId): array
    {
        // Get attachments for this resource
        $attachments = DatabaseModel::select([
            'select' => ['res_id'],
            'table'  => ['res_attachments'],
            'where'  => ['res_id_master = ?'],
            'data'   => [$resId]
        ]);
        $attachmentIds = array_column($attachments, 'res_id');

        // Build query conditions
        $where = '(document_id = ? and document_type = ?)';
        $data = [$resId, 'resource'];

        if (!empty($attachmentIds)) {
            $where .= ' or (document_id in (?) and document_type = ?)';
            $data[] = $attachmentIds;
            $data[] = 'attachment';
        }

        // Fetch shippings data
        $shippingsData = DatabaseModel::select([
            'select' => [
                'id',
                'user_id',
                'sending_id',
                'document_id',
                'document_type',
                'options',
                'fee',
                'recipient_entity_id',
                'recipients',
                'account_id',
                'creation_date',
                'history',
                'action_id',
                'maileva_template_id'
            ],
            'table'  => ['shippings'],
            'where'  => [$where],
            'data'   => $data
        ]);

        // Repositories for related entities
        $userRepository = new UserRepository();
        $templateRepository = new TemplateRepository();
        $entityRepository = new EntityRepository();
        $mainResourceRepository = new MainResourceRepository($userRepository, $templateRepository, $entityRepository);
        $attachmentRepository = new AttachmentRepository(
            $userRepository,
            $mainResourceRepository,
            $templateRepository,
            new ContactRepository()
        );

        $shippings = [];
        $processedSendingIds = [];

        foreach ($shippingsData as $shippingData) {
            // Skip duplicates by sending_id
            if (in_array($shippingData['sending_id'], $processedSendingIds)) {
                continue;
            }

            $processedSendingIds[] = $shippingData['sending_id'];

            // Create and populate a Shipping object
            $shipping = new Shipping();
            $shipping->setId($shippingData['id']);
            $shipping->setSendingId($shippingData['sending_id']);

            // Get and set User
            $user = $userRepository->getUserById($shippingData['user_id']);
            $shipping->setUser($user);

            // Get and set Resource
            if ($shippingData['document_type'] === 'resource') {
                $resource = $mainResourceRepository->getMainResourceByResId($shippingData['document_id']);
            } else {
                $resource = $attachmentRepository->getAttachmentByResId($shippingData['document_id']);
            }
            $shipping->setResource($resource);

            // Set other properties
            $shipping->setOptions(json_decode($shippingData['options'], true) ?? []);
            $shipping->setFee((float)$shippingData['fee']);

            // Get and set Entity
            $entity = $entityRepository->getEntityById($shippingData['recipient_entity_id']);
            $shipping->setRecipientEntity($entity);

            $shipping->setRecipients(json_decode($shippingData['recipients'], true) ?? []);
            $shipping->setAccountId($shippingData['account_id']);
            $shipping->setCreationDate(new DateTimeImmutable($shippingData['creation_date']));
            $shipping->setHistory(json_decode($shippingData['history'] ?? '[]', true));
            $shipping->setActionId($shippingData['action_id']);

            if (!empty($shippingData['maileva_template_id'])) {
                $shipping->setMailevaTemplate(
                    $this->mailevaTemplateRepository->getById($shippingData['maileva_template_id'])
                );
            }

            $shippings[] = $shipping;
        }

        return $shippings;
    }

    /**
     * @param string $sendingId
     * @return Shipping|null
     * @throws Exception
     */
    public function getMailevaShippingBySendingId(string $sendingId): ?Shipping
    {
        $shipping = DatabaseModel::select([
            'select' => [
                '*'
            ],
            'table'  => ['shippings'],
            'where'  => ['sending_id = ?'],
            'data'   => [$sendingId]
        ]);

        if (empty($shipping[0])) {
            return null;
        }

        return $this->buildShipping($shipping[0]);
    }

    /**
     * @param array $data
     * @return Shipping
     * @throws Exception
     */
    private function buildShipping(array $data): Shipping
    {
        $userRepository = new UserRepository();
        $templateRepository = new TemplateRepository();
        $entityRepository = new EntityRepository();
        $mainResourceRepository = new MainResourceRepository($userRepository, $templateRepository, $entityRepository);
        $attachmentRepository = new AttachmentRepository(
            $userRepository,
            $mainResourceRepository,
            $templateRepository,
            new ContactRepository()
        );


        if ($data['document_type'] === 'resource') {
            $resource = $mainResourceRepository->getMainResourceByResId($data['document_id']);
        } else {
            $resource = $attachmentRepository->getAttachmentByResId($data['document_id']);
        }

        return (new Shipping())
            ->setId($data['id'])
            ->setSendingId($data['sending_id'])
            ->setUser($userRepository->getUserById($data['user_id']))
            ->setResource($resource)
            ->setOptions(json_decode($data['options'], true) ?? [])
            ->setFee((float)$data['fee'])
            ->setRecipientEntity($entityRepository->getEntityById($data['recipient_entity_id']))
            ->setMailevaTemplate($this->mailevaTemplateRepository->getById($data['maileva_template_id']))
            ->setRecipients(json_decode($data['recipients'], true) ?? [])
            ->setAccountId($data['account_id'])
            ->setActionId($data['action_id']);
    }
}
