<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Watermark Configuration
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Watermark\Application;

use MaarchCourrier\Authorization\Domain\Privilege\AdminParameterPrivilege;
use MaarchCourrier\Authorization\Domain\Privilege\AdminWatermarkAttachmentParametersPrivilege;
use MaarchCourrier\Authorization\Domain\Privilege\AdminWatermarkParametersPrivilege;
use MaarchCourrier\Authorization\Domain\Problem\ServiceForbiddenProblem;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;
use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\SignatureBook\Port\RetrieveSignatureBookWatermarkConfigFactoryInterface;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;

class RetrieveWatermarkConfiguration
{
    public function __construct(
        private readonly PrivilegeCheckerInterface $privilegeChecker,
        private readonly CurrentUserInterface $currentUser,
        private readonly ConfigurationRepositoryInterface $configurationRepository,
        private readonly EnvironmentInterface $environment,
        private readonly RetrieveSignatureBookWatermarkConfigFactoryInterface $retrieveSBWatermarkConfigFactory
    ) {
    }

    /**
     * @return array
     * @throws ServiceForbiddenProblem
     */
    public function get(): array
    {
        if (
            !$this->privilegeChecker->hasPrivilege($this->currentUser->getCurrentUser(), new AdminParameterPrivilege())
        ) {
            throw new ServiceForbiddenProblem();
        }

        $watermarkConfigurations = $this->configurationRepository->getByPrivilege(
            new AdminWatermarkParametersPrivilege()
        );
        if (empty($watermarkConfigurations)) {
            return ['configuration' => null];
        }

        $configuration = ['documents' => $watermarkConfigurations->getValue()];

        $watermarkAttachmentConfigurations = $this->configurationRepository->getByPrivilege(
            new AdminWatermarkAttachmentParametersPrivilege()
        );

        $configuration['attachments'] = $watermarkAttachmentConfigurations->getValue();

        if ($this->environment->isNewInternalParapheurEnabled()) {
            $sbElectronicWatermarkConfig = ($this->retrieveSBWatermarkConfigFactory->create())->execute();
            $configuration['electronicSignature'] = $sbElectronicWatermarkConfig;
        }

        return ['configuration' => $configuration];
    }
}
