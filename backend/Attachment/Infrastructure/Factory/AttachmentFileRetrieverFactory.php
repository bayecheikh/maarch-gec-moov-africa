<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment File Retriever Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Attachment\Infrastructure\Factory;

use MaarchCourrier\Attachment\Application\AttachmentFileRetriever;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentFileRetrieverFactoryInterface;
use MaarchCourrier\DocumentConversion\Infrastructure\Service\ConvertPdfService;
use MaarchCourrier\DocumentStorage\Application\FilePathBuilder;
use MaarchCourrier\DocumentStorage\Infrastructure\Repository\DocServerRepository;
use MaarchCourrier\DocumentStorage\Infrastructure\Repository\DocServerTypeRepository;
use MaarchCourrier\DocumentStorage\Infrastructure\Service\FileSystemService;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;

class AttachmentFileRetrieverFactory implements AttachmentFileRetrieverFactoryInterface
{
    public static function create(): AttachmentFileRetriever
    {
        $userRepository = new UserRepository();
        $templateRepository = new TemplateRepository();

        return new AttachmentFileRetriever(
            new AttachmentRepository(
                $userRepository,
                new MainResourceRepository($userRepository, $templateRepository, new EntityRepository()),
                $templateRepository,
                new ContactRepository()
            ),
            new ConvertPdfService(),
            new FilePathBuilder(
                new DocServerRepository(new DocServerTypeRepository()),
                new FileSystemService()
            )
        );
    }
}
