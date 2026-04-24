<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Send Document To Sign Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Factory;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Factory\AttachmentFileRetrieverFactory;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\Core\Infrastructure\Curl\CurlService;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagRetrieveConfiguration;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagSendDocumentToSign;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\ExternalSignatureBookConfigService;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Service\GoodflagApiService;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Service\GoodflagWorkflowDatabaseService;
use MaarchCourrier\MainResource\Infrastructure\Factory\RetrieveMainResourceFileFactory;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;

class GoodflagSendDocumentToSignFactory
{
    /**
     * @throws Exception
     */
    public static function create(): GoodflagSendDocumentToSign
    {
        $configurationRepository = new ConfigurationRepository();
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $goodflagRetrieveConfiguration = new GoodflagRetrieveConfiguration(
            $configurationRepository,
            new ExternalSignatureBookConfigService()
        );

        $goodflagApiService = new GoodflagApiService($logger, new CurlService(), $goodflagRetrieveConfiguration);

        $userRepository = new UserRepository();
        $templateRepository = new TemplateRepository();
        $entityRepository = new EntityRepository();
        $mainResourceRepository = new MainResourceRepository($userRepository, $templateRepository, $entityRepository);

        return new GoodflagSendDocumentToSign(
            $goodflagRetrieveConfiguration,
            $goodflagApiService,
            $mainResourceRepository,
            new RetrieveMainResourceFileFactory(),
            new AttachmentFileRetrieverFactory(),
            new GoodflagWorkflowDatabaseService($logger),
            new CurrentUserInformations()
        );
    }
}
