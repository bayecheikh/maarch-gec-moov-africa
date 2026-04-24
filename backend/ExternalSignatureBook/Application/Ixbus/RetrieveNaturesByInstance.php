<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Natures class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Ixbus;

use MaarchCourrier\Core\Domain\Port\CurlErrorInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\CouldNotGetIxbusNaturesFromApiServiceProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\IxbusInstanceIdIsNotDefinedProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\IxbusApiServiceInterface;

class RetrieveNaturesByInstance
{
    public function __construct(
        private readonly RetrieveConfig $retrieveConfig,
        private readonly IxbusApiServiceInterface $apiService
    ) {
    }

    public function getNatures(?string $instanceId): array
    {
        if (empty($instanceId)) {
            throw new IxbusInstanceIdIsNotDefinedProblem();
        }

        $config = $this->retrieveConfig->get();

        $instanceConfig = array_filter($config->getInstances(), function ($item) use ($instanceId) {
            return $item->getId() === $instanceId;
        });
        $instanceConfig = array_values($instanceConfig)[0];

        $natures = $this->apiService->getNatures($instanceConfig);
        if ($natures instanceof CurlErrorInterface) {
            throw new CouldNotGetIxbusNaturesFromApiServiceProblem($natures);
        }

        return $natures;
    }
}
