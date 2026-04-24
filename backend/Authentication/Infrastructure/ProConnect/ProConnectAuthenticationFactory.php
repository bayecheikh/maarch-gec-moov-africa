<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ProConnect authentication Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Infrastructure\ProConnect;

use Exception;
use MaarchCourrier\Authentication\Application\ProConnect\ProConnectAuthentication;
use MaarchCourrier\Authentication\Application\ProConnect\ProConnectConfiguration;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\ProConnectConfigNotFoundProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\ProConnectIsDisabledProblem;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;

class ProConnectAuthenticationFactory
{
    /**
     * @return ProConnectAuthentication
     * @throws ProConnectConfigNotFoundProblem
     * @throws ProConnectIsDisabledProblem|Exception
     */
    public static function create(): ProConnectAuthentication
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');
        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $proConnectConfiguration = new ProConnectConfiguration(new ConfigurationRepository());

        return new ProConnectAuthentication(
            $logger,
            $proConnectConfiguration,
            new ProConnectApiService($logger),
            new UserRepository()
        );
    }
}
