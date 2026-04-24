<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Process Webhook Event Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Factory;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\Core\Infrastructure\Curl\CurlService;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagCheckWebhookEvent;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagProcessWebhookEvent;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagRetrieveAndSaveSignedFile;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagRetrieveConfiguration;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\ExternalSignatureBookConfigService;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Service\GoodflagApiService;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Service\GoodflagWorkflowDatabaseService;
use MaarchCourrier\History\Application\AddHistoryRecord;
use MaarchCourrier\History\Infrastructure\Repository\HistoryRepository;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\Note\Infrastructure\Repository\NoteRepository;
use MaarchCourrier\Notification\Infrastructure\Service\NotificationsEventsService;
use MaarchCourrier\SignatureBook\Infrastructure\Service\CreateVersionService;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;

class GoodflagProcessWebhookEventFactory
{
    /**
     * @return GoodflagProcessWebhookEvent
     * @throws Exception
     */
    public static function create(): GoodflagProcessWebhookEvent
    {
        $configurationRepository = new ConfigurationRepository();
        $externalSignatureBookConfigService = new ExternalSignatureBookConfigService();
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
            $externalSignatureBookConfigService
        );
        $goodflagApiService = new GoodflagApiService($logger, new CurlService(), $goodflagRetrieveConfiguration);

        $goodflagCheckWebhookEvent = new GoodflagCheckWebhookEvent($goodflagApiService);

        $userRepository = new UserRepository();
        $templateRepository = new TemplateRepository();
        $entityRepository = new EntityRepository();
        $mainResourceRepository = new MainResourceRepository($userRepository, $templateRepository, $entityRepository);

        $attachmentRepository = new AttachmentRepository(
            $userRepository,
            $mainResourceRepository,
            $templateRepository,
            new ContactRepository()
        );

        $addHistoryRecord = new AddHistoryRecord(
            $logger,
            new HistoryRepository(),
            new NotificationsEventsService()
        );

        $goodflagRetrieveAndSaveSignedFile = new GoodflagRetrieveAndSaveSignedFile(
            $goodflagApiService,
            new GoodflagWorkflowDatabaseService($logger),
            $mainResourceRepository,
            $attachmentRepository,
            new CreateVersionService()
        );

        return new GoodflagProcessWebhookEvent(
            $goodflagApiService,
            $goodflagCheckWebhookEvent,
            $mainResourceRepository,
            $attachmentRepository,
            new GoodflagWorkflowDatabaseService($logger),
            $addHistoryRecord,
            new NoteRepository($userRepository),
            $goodflagRetrieveAndSaveSignedFile
        );
    }
}
