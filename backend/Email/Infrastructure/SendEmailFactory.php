<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Microsoft Email Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Infrastructure;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Factory\AttachmentFileRetrieverFactory;
use MaarchCourrier\Core\Domain\Problem\Configuration\EmailServerConfigurationNotFoundProblem;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\DocumentStorage\Infrastructure\Service\FileSystemService;
use MaarchCourrier\Email\Application\SendEmail;
use MaarchCourrier\Email\Domain\AdminEmailServerPrivilege;
use MaarchCourrier\Email\Infrastructure\Adapter\MicrosoftEmailAdapter;
use MaarchCourrier\Email\Infrastructure\Adapter\PhpMailerServiceAdapter;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\MainResource\Infrastructure\Factory\RetrieveMainResourceFileFactory;
use MaarchCourrier\MainResource\Infrastructure\Factory\RetrieveOriginalMainResourceFileFactory;
use MaarchCourrier\MessageExchange\Infrastructure\Service\MessageExchangeFileService;
use MaarchCourrier\Note\Infrastructure\Factory\GenerateEncodedPdfFactory;
use Psr\Log\LoggerInterface;

class SendEmailFactory
{
    /**
     * @throws EmailServerConfigurationNotFoundProblem
     * @throws Exception
     */
    public static function create(LoggerInterface $logger): SendEmail
    {
        $configRepository = new ConfigurationRepository();
        $config = $configRepository->getByPrivilege(new AdminEmailServerPrivilege());

        if ($config == null) {
            throw new EmailServerConfigurationNotFoundProblem();
        }

        $entityRepository = new EntityRepository();
        $messageExchangeFileService = new MessageExchangeFileService();
        $generateEncodedPdfFactory = new GenerateEncodedPdfFactory();

        if ($config->getValue()['type'] == 'microsoftOAuth') {
            $sendEmail = new SendEmail(
                new MicrosoftEmailAdapter(new FileSystemService(), $logger),
                $entityRepository,
                $messageExchangeFileService,
                new RetrieveOriginalMainResourceFileFactory(),
                new RetrieveMainResourceFileFactory(),
                new AttachmentFileRetrieverFactory(),
                $generateEncodedPdfFactory
            );
        } else {
            $sendEmail = new SendEmail(
                new PhpMailerServiceAdapter($logger),
                $entityRepository,
                $messageExchangeFileService,
                new RetrieveOriginalMainResourceFileFactory(),
                new RetrieveMainResourceFileFactory(),
                new AttachmentFileRetrieverFactory(),
                $generateEncodedPdfFactory
            );
        }

        return $sendEmail->setEmailServerConfig($config);
    }
}
