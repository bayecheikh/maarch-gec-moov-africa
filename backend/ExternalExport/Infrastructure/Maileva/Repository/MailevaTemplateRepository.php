<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Template Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Infrastructure\Maileva\Repository;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\ExternalExport\Domain\Maileva\MailevaTemplate;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\MailevaTemplateRepositoryInterface;
use SrcCore\models\DatabaseModel;

class MailevaTemplateRepository implements MailevaTemplateRepositoryInterface
{
    /**
     * @param int $id
     *
     * @return MailevaTemplate|null
     * @throws Exception
     */
    public function getById(int $id): ?MailevaTemplate
    {
        $shipping = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['shipping_templates'],
            'where'  => ['id = ?'],
            'data'   => [$id]
        ]);

        if (empty($shipping[0])) {
            return null;
        }

        return $this->buildTemplate($shipping[0]);
    }

    /**
     * @param array $ids
     *
     * @return MailevaTemplate[]
     * @throws Exception
     */
    public function getByEntityIds(array $ids): array
    {
        $shippingTemplates = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['shipping_templates'],
            'where'  => ['entities @> ?'],
            'data'   => [json_encode($ids)]
        ]);

        if (empty($shippingTemplates)) {
            return [];
        }

        $templates = [];
        foreach ($shippingTemplates as $templateData) {
            $templates[] = $this->buildTemplate($templateData);
        }

        return $templates;
    }

    /**
     * Helper method to convert database row to a MailevaTemplate.
     *
     * @param array $data
     * @return MailevaTemplate
     * @throws Exception
     */
    private function buildTemplate(array $data): MailevaTemplate
    {
        return (new MailevaTemplate())
            ->setId($data['id'])
            ->setLabel($data['label'])
            ->setDescription($data['description'])
            ->setOptions(json_decode($data['options'] ?? '[]', true))
            ->setFee(json_decode($data['fee'] ?? '[]', true))
            ->setEntities(json_decode($data['entities'] ?? '[]', true))
            ->setAccount(json_decode($data['account'] ?? '[]', true))
            ->setSubscriptions(json_decode($data['subscriptions'] ?? '[]', true))
            ->setTokenMinLat(new DateTimeImmutable($data['token_min_lat'] ?? 'now'));
    }
}
