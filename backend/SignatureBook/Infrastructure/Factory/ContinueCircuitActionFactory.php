<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ContinueCircuitActionFactory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Basket\Infrastructure\Repository\BasketRepository;
use MaarchCourrier\Basket\Infrastructure\Repository\RedirectBasketRepository;
use MaarchCourrier\Basket\Infrastructure\Service\BasketClauseService;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Group\Infrastructure\Repository\GroupRepository;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\SignatureBook\Application\Action\Checker\SignatureBookActionPermissionChecker;
use MaarchCourrier\SignatureBook\Application\Action\ContinueCircuitAction;
use MaarchCourrier\SignatureBook\Application\Resource\CreateAnnotatedResourceVersion;
use MaarchCourrier\SignatureBook\Application\Resource\UpdateResourceContentInSignatoryBook;
use MaarchCourrier\SignatureBook\Application\Resource\UseCase\CreateAnnotatedVersionAndSynchroInSignatoryBook;
use MaarchCourrier\SignatureBook\Application\Workflow\UpdateWorkflowInSignatureBook;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurSignatureService;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\VisaWorkflowRepository;
use MaarchCourrier\SignatureBook\Infrastructure\Service\CreateVersionService;
use MaarchCourrier\SignatureBook\Infrastructure\Service\MaarchParapheurResourceService;
use MaarchCourrier\SignatureBook\Infrastructure\Service\SignatureBookWorkflowService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;

class ContinueCircuitActionFactory
{
    /**
     * @throws Exception
     */
    public static function create(): ContinueCircuitAction
    {
        $currentUser = new CurrentUserInformations();
        $signatureService = new MaarchParapheurSignatureService();
        $signatureServiceConfigLoader = new SignatureServiceJsonConfigLoader();

        $userRepository = new UserRepository();
        $templateRepository = new TemplateRepository();
        $mainResourceRepository = new MainResourceRepository(
            $userRepository,
            $templateRepository,
            new EntityRepository()
        );
        $visaWorkflowRepository = new VisaWorkflowRepository($userRepository);
        $attachmentRepository = new AttachmentRepository(
            $userRepository,
            $mainResourceRepository,
            $templateRepository,
            new ContactRepository()
        );

        $updateWorkflowInSignatureBook = new UpdateWorkflowInSignatureBook(
            $signatureServiceConfigLoader,
            new SignatureBookWorkflowService(),
            $userRepository,
            $mainResourceRepository,
            $attachmentRepository
        );

        $createVersionService = new CreateVersionService();

        $createVersionResource = new CreateAnnotatedResourceVersion(
            $mainResourceRepository,
            $attachmentRepository,
            $createVersionService
        );

        $updateResourceInSignatoryBook = new UpdateResourceContentInSignatoryBook(
            new SignatureServiceJsonConfigLoader(),
            new MaarchParapheurResourceService(),
            $currentUser
        );

        $createVersionResourceAndSynchroSignatoryBook = new CreateAnnotatedVersionAndSynchroInSignatoryBook(
            $createVersionResource,
            $updateResourceInSignatoryBook
        );

        return new ContinueCircuitAction(
            $currentUser,
            $signatureService,
            $signatureServiceConfigLoader,
            $mainResourceRepository,
            $visaWorkflowRepository,
            $updateWorkflowInSignatureBook,
            new SignatureBookActionPermissionChecker(
                new BasketRepository(),
                new BasketClauseService(),
                new GroupRepository(),
                $mainResourceRepository,
                $visaWorkflowRepository,
                new RedirectBasketRepository()
            ),
            $createVersionResourceAndSynchroSignatoryBook
        );
    }
}
