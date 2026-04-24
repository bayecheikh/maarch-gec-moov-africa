<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrive Ixbus Config class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Ixbus;

use MaarchCourrier\ExternalSignatureBook\Domain\ExternalSignatureBookType;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\IxbusConfig;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\IxbusInstanceConfig;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port\IxbusCommonConfigInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port\IxbusConfigInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port\IxbusInstanceConfigInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\IxbusDuplicatedInstanceIdConfigProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\IxbusMissingAttributeConfigProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\ExternalSignatureBookConfigServiceInterface;

class RetrieveConfig
{
    public function __construct(
        private readonly ExternalSignatureBookConfigServiceInterface $externalSignatureBookConfigService
    ) {
    }

    /**
     * @return IxbusConfigInterface
     * @throws IxbusMissingAttributeConfigProblem
     * @throws IxbusDuplicatedInstanceIdConfigProblem
     */
    public function get(): IxbusConfigInterface
    {
        $xml = $this->externalSignatureBookConfigService->getConfigById(ExternalSignatureBookType::IXBUS);

        $config = (new IxbusConfig())->setId($xml->id);

        // check if old config exist
        if (
            !empty($xml->tokenAPI ?? null) &&
            !empty($xml->url ?? null) &&
            !empty($xml->optionSendOfficeDocument ?? null) &&
            empty($xml->instances ?? null) && empty($xml->instances->instance ?? null)
        ) {
            $optionSendOfficeDocument = filter_var(
                (string)$xml->optionSendOfficeDocument,
                FILTER_VALIDATE_BOOLEAN
            );
            $config->setTokenAPI($xml->tokenAPI)
                ->setUrl($xml->url)
                ->setOptionSendOfficeDocument($optionSendOfficeDocument)
                ->setInstances(null);
        } else {
            if (empty($xml->instances ?? null) || empty($xml->instances->instance ?? null)) {
                throw new IxbusMissingAttributeConfigProblem('instances');
            }

            /**
             * @var IxbusInstanceConfigInterface[] $instances
             */
            $instances = [];
            $instanceIds = [];

            for ($i = 0; $i < count($xml->instances->instance); $i++) {
                $instance = $xml->instances->instance[$i];

                if (empty($instance->id ?? null)) {
                    throw new IxbusMissingAttributeConfigProblem('instance.id', $i);
                } elseif (in_array($instance->id, $instanceIds, true)) {
                    throw new IxbusDuplicatedInstanceIdConfigProblem($instance->id);
                }
                $instanceIds[] = $instance->id;

                $optionSendOfficeDocument = false;
                if (isset($instance->optionSendOfficeDocument)) {
                    $optionSendOfficeDocument = filter_var(
                        (string)$instance->optionSendOfficeDocument,
                        FILTER_VALIDATE_BOOLEAN
                    );
                }

                $instances[] = (new IxbusInstanceConfig())
                    ->setId($instance->id ?? '')
                    ->setLabel($instance->label ?? '')
                    ->setTokenAPI($instance->tokenAPI ?? '')
                    ->setUrl($instance->url ?? '')
                    ->setOptionSendOfficeDocument($optionSendOfficeDocument);
            }

            $config->setInstances($instances);
        }


        return $config;
    }

    public function getByInstance(?string $instanceId): IxbusCommonConfigInterface
    {
        $config = $this->get();

        if ($config->isNewConfig()) {
            if (!empty($instanceId)) {
                $config = array_filter($config->getInstances(), function ($item) use ($instanceId) {
                    return $item->getId() == $instanceId;
                });

                $config = array_values($config)[0];
            } else {
                $config = $config->getInstances()[0];
            }
        }

        return $config;
    }
}
