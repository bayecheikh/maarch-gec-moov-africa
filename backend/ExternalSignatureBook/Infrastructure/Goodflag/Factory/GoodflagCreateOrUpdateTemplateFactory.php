<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Create Or Update Template Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Factory;

use Exception;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\Core\Infrastructure\Curl\CurlService;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagCheckTemplate;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagCreateTemplate;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagGetConsentPages;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagGetSignatureProfiles;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagRetrieveConfiguration;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagUpdateTemplate;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\ExternalSignatureBookConfigService;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Service\GoodflagApiService;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;

class GoodflagCreateOrUpdateTemplateFactory
{
    /**
     * @throws Exception
     */
    public static function create(string $mode): GoodflagCreateTemplate|GoodflagUpdateTemplate
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
        $getSignaturesProfiles = new GoodflagGetSignatureProfiles($goodflagApiService);
        $getConsentPages = new GoodflagGetConsentPages($goodflagApiService);
        $goodflagCheckTemplate = new GoodflagCheckTemplate($getSignaturesProfiles, $getConsentPages);

        if ($mode === 'create') {
            return new GoodflagCreateTemplate(
                $configurationRepository,
                $goodflagCheckTemplate,
                $goodflagApiService
            );
        } else {
            return new GoodflagUpdateTemplate(
                $configurationRepository,
                $goodflagCheckTemplate
            );
        }
    }
}
