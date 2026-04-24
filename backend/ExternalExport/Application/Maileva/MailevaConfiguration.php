<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Fetch Maileva Configuration class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Application\Maileva;

use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\MailevaPrivilege;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaConfigNotFoundProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaIsDisabledProblem;

class MailevaConfiguration
{
    // Constants for Maileva environments
    public const MAILEVA_PRODUCTION_URI = 'https://api.maileva.com';
    public const MAILEVA_PRODUCTION_CLIENT_ID = 'MAARCH';
    public const MAILEVA_PRODUCTION_CLIENT_SECRET = 'Rh28hvVp3xKWVBE5nxyuTMOKschpwwyj';
    public const MAILEVA_SANDBOX_URI = 'https://api.sandbox.maileva.net';
    public const MAILEVA_SANDBOX_CLIENT_ID = 'MAARCH-sandbox';
    public const MAILEVA_SANDBOX_CLIENT_SECRET = 'LDgZbqrqSp4dQx4HBA5lamvPh6bvVvjs';

    public function __construct(
        public ConfigurationRepositoryInterface $configurationRepository,
    ) {
    }

    /**
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     */
    public function getMailevaConfiguration(): array
    {
        $mailevaConfig = ['enabled' => false];

        $configuration = $this->configurationRepository->getByPrivilege(new MailevaPrivilege());

        if ($configuration == null) {
            throw new MailevaConfigNotFoundProblem();
        }

        $configuration = $configuration->getValue();

        if (!($configuration['enabled'] ?? false)) {
            throw new MailevaIsDisabledProblem();
        }

        $mailevaConfig['enabled'] = $configuration['enabled'];
        $mailevaConfig['connectionUri'] = rtrim($configuration['authUri'], '/');
        $mailevaConfig['uri'] = rtrim($configuration['uri'], '/');

        if (!empty($mailevaConfig['uri']) && $mailevaConfig['uri'] == self::MAILEVA_PRODUCTION_URI) {
            $mailevaConfig['clientId'] = self::MAILEVA_PRODUCTION_CLIENT_ID;
            $mailevaConfig['clientSecret'] = self::MAILEVA_PRODUCTION_CLIENT_SECRET;
        }
        if (!empty($mailevaConfig['uri']) && $mailevaConfig['uri'] == self::MAILEVA_SANDBOX_URI) {
            $mailevaConfig['clientId'] = self::MAILEVA_SANDBOX_CLIENT_ID;
            $mailevaConfig['clientSecret'] = self::MAILEVA_SANDBOX_CLIENT_SECRET;
        }

        return $mailevaConfig;
    }
}
