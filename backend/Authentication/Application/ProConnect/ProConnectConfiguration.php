<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ProConnect Configuration class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Application\ProConnect;

use MaarchCourrier\Authentication\Domain\ProConnect\Problem\ProConnectConfigNotFoundProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\ProConnectIsDisabledProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\ProConnectPrivilege;
use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;

class ProConnectConfiguration
{
    public function __construct(
        public ConfigurationRepositoryInterface $configurationRepository,
    ) {
    }

    /**
     * @return array
     * @throws ProConnectConfigNotFoundProblem
     * @throws ProConnectIsDisabledProblem
     */
    public function getProConnectConfiguration(): array
    {
        $proConnectConfig = ['enabled' => false];
        $configuration = $this->configurationRepository->getByPrivilege(new ProConnectPrivilege());
        if ($configuration == null) {
            throw new ProConnectConfigNotFoundProblem();
        }

        $configuration = $configuration->getValue();
        if (!($configuration['enabled'] ?? false)) {
            throw new ProConnectIsDisabledProblem();
        }

        $proConnectConfig['enabled'] = $configuration['enabled'];
        $proConnectConfig['connectionUrl'] = $configuration['url'];
        $proConnectConfig['clientId'] = $configuration['clientId'];
        $proConnectConfig['clientSecret'] = $configuration['clientSecret'];

        return $proConnectConfig;
    }
}
