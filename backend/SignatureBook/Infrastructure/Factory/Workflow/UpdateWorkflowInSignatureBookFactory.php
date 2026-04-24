<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Update Workflow In Signature Book Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory\Workflow;

use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\SignatureBook\Application\Workflow\UpdateWorkflowInSignatureBook;
use MaarchCourrier\SignatureBook\Infrastructure\Service\SignatureBookWorkflowService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;

class UpdateWorkflowInSignatureBookFactory
{
    public function create(): UpdateWorkflowInSignatureBook
    {
        $templateRepository = new TemplateRepository();
        $userRepository = new UserRepository();
        $mainResourceRepository = new MainResourceRepository(
            $userRepository,
            $templateRepository,
            new EntityRepository()
        );

        return new UpdateWorkflowInSignatureBook(
            new SignatureServiceJsonConfigLoader(),
            new SignatureBookWorkflowService(),
            new UserRepository(),
            $mainResourceRepository,
            new AttachmentRepository(
                $userRepository,
                $mainResourceRepository,
                $templateRepository,
                new ContactRepository()
            )
        );
    }
}
