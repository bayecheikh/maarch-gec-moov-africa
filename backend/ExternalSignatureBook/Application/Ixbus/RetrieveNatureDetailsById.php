<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Nature Details By Id class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Ixbus;

use MaarchCourrier\Core\Domain\Port\CurlErrorInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\CouldNotGetIxbusEditorUsersFromNatureApiServiceProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\CouldNotGetIxbusNatureInfoFromApiServiceProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\IxbusApiServiceInterface;

class RetrieveNatureDetailsById
{
    public function __construct(
        private readonly RetrieveConfig $config,
        private readonly IxbusApiServiceInterface $apiService
    ) {
    }

    public function get(?string $instanceId, string $natureId): array
    {
        $config = $this->config->get();

        if (!empty($instanceId) && $config->isNewConfig()) {
            $config = array_filter($config->getInstances(), function ($item) use ($instanceId) {
                return $item->getId() == $instanceId;
            });
            $config = array_values($config)[0];
        }

        $models = $this->apiService->getNatureById($config, $natureId);
        if ($models instanceof CurlErrorInterface) {
            throw new CouldNotGetIxbusNatureInfoFromApiServiceProblem($models, $natureId);
        }

        foreach ($models as $key => $value) {
            unset($models[$key]['etapes']);
            unset($models[$key]['options']);
        }

        $users = $this->apiService->getEditorUsersFromNatureById($config, $natureId);
        if ($users instanceof CurlErrorInterface) {
            throw new CouldNotGetIxbusEditorUsersFromNatureApiServiceProblem($users, $natureId);
        }

        return [
            'messageModels' => $models,
            'users'         => $users,
        ];
    }
}
