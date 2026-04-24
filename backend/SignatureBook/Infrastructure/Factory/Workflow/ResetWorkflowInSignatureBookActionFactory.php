<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Reset Workflow In Signature Book Factory
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory\Workflow;

use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\SignatureBook\Application\Action\ResetWorkflowInSignatureBookAction;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\VisaWorkflowRepository;
use MaarchCourrier\SignatureBook\Infrastructure\Service\SignatureBookLinkService;
use MaarchCourrier\SignatureBook\Infrastructure\Service\SignatureBookWorkflowService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;

class ResetWorkflowInSignatureBookActionFactory
{
    public function create(): ResetWorkflowInSignatureBookAction
    {
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
        $signatureBookWorkflowService = new SignatureBookWorkflowService();

        return new ResetWorkflowInSignatureBookAction(
            new Environment(),
            new SignatureServiceJsonConfigLoader(),
            $signatureBookWorkflowService,
            new VisaWorkflowRepository(new UserRepository()),
            $mainResourceRepository,
            $attachmentRepository,
            new SignatureBookLinkService(
                $signatureBookWorkflowService,
                $mainResourceRepository,
                $attachmentRepository
            )
        );
    }
}
