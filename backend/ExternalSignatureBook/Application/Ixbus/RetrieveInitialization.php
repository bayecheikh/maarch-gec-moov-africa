<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Instances class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Ixbus;


use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\IxbusDuplicatedInstanceIdConfigProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\IxbusMissingAttributeConfigProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\IxbusApiServiceInterface;

class RetrieveInitialization
{
    public function __construct(
        private readonly RetrieveConfig $config,
        private readonly IxbusApiServiceInterface $apiService
    ) {
    }

    /**
     * @return array|array[]
     * @throws IxbusMissingAttributeConfigProblem
     * @throws IxbusDuplicatedInstanceIdConfigProblem
     */
    public function getInitialize(): array
    {
        $config = $this->config->get();

        if ($config->isNewConfig()) {
            $instances = [];
            foreach ($config->getInstances() as $instance) {
                $instances[] = ['id' => $instance->getId(), 'label' => $instance->getLabel()];
            }

            return [
                'instances' => $instances
            ];
        } else {
            $natures = $this->apiService->getNatures($config);

            return [
                'natures' => $natures
            ];
        }
    }
}
