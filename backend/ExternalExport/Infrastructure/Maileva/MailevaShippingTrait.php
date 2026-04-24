<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Shipping Trait
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Infrastructure\Maileva;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Factory\AttachmentFileRetrieverFactory;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Contact\Infrastructure\Service\AfnorContactService;
use MaarchCourrier\Core\Domain\Contact\Problem\PrimaryEntityAddressOfCurrentUserIsNotFilledEnoughProblem;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\DocumentStorage\Infrastructure\Service\FileSystemService;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\ExternalExport\Application\Maileva\MailevaContactExporter;
use MaarchCourrier\ExternalExport\Application\Maileva\MailevaConfiguration;
use MaarchCourrier\ExternalExport\Application\Maileva\MailevaDocumentToSendPreparation;
use MaarchCourrier\ExternalExport\Application\Maileva\MailevaShippingFeeCalculation;
use MaarchCourrier\ExternalExport\Application\Maileva\SendToMaileva;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaConfigNotFoundProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaEreSenderNotFoundInTemplateProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaIsDisabledProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaTemplateNotFoundProblem;
use MaarchCourrier\ExternalExport\Infrastructure\Maileva\Repository\MailevaTemplateRepository;
use MaarchCourrier\ExternalExport\Infrastructure\Maileva\Repository\ShippingRepository;
use MaarchCourrier\ExternalExport\Infrastructure\Maileva\Service\MailevaApiService;
use MaarchCourrier\ExternalExport\Infrastructure\Service\ExternalFieldUpdaterService;
use MaarchCourrier\MainResource\Infrastructure\Factory\RetrieveMainResourceFileFactory;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\MainResource\Infrastructure\Repository\ResourceContactsRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;

trait MailevaShippingTrait
{
    /**
     * @param array $args
     * @return array
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     * @throws MailevaTemplateNotFoundProblem
     * @throws PrimaryEntityAddressOfCurrentUserIsNotFilledEnoughProblem
     * @throws MailevaEreSenderNotFoundInTemplateProblem
     * @throws Exception
     */
    public static function sendMailevaShippingAction(array $args): array
    {
        $environment = new Environment();
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');
        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $configurationRepository = new ConfigurationRepository();
        $userRepository = new UserRepository();
        $templateRepository = new TemplateRepository();
        $mainResourceRepository = new MainResourceRepository(
            $userRepository,
            $templateRepository,
            new EntityRepository()
        );
        $attachmentRepository = new AttachmentRepository(
            $userRepository,
            $mainResourceRepository,
            $templateRepository,
            new ContactRepository()
        );

        $afnorContactService = new AfnorContactService();
        $mailevaContactExporter = new MailevaContactExporter(
            $logger,
            $afnorContactService
        );

        $retrieveMainResourceFileFactory = new RetrieveMainResourceFileFactory();
        $attachmentFileRetrieverFactory = new AttachmentFileRetrieverFactory();
        $fileSystemService = new FileSystemService();

        $mailevaDocumentToSendPreparation = new MailevaDocumentToSendPreparation(
            $environment,
            $logger,
            $mainResourceRepository,
            $attachmentRepository,
            new ResourceContactsRepository(),
            $mailevaContactExporter,
            $retrieveMainResourceFileFactory,
            $attachmentFileRetrieverFactory,
            $fileSystemService
        );

        $mailevaShippingFeeCalculation = new MailevaShippingFeeCalculation(
            $retrieveMainResourceFileFactory,
            $attachmentFileRetrieverFactory,
            $fileSystemService
        );

        $mailevaTemplateRepository = new MailevaTemplateRepository();

        $potentialWarnings = (new SendToMaileva(
            $environment,
            $logger,
            new MailevaConfiguration($configurationRepository),
            $mailevaTemplateRepository,
            $mailevaDocumentToSendPreparation,
            new MailevaApiService($logger, $mailevaContactExporter),
            $afnorContactService,
            new ExternalFieldUpdaterService($mainResourceRepository, $attachmentRepository),
            new CurrentUserInformations(),
            new ShippingRepository($mailevaTemplateRepository),
            $mailevaShippingFeeCalculation
        ))->send($args['action']['id'], $args['data']['shippingTemplateId'], $args['resources']);

        return ['data' => array_merge($args['data'], $potentialWarnings)];
    }
}
