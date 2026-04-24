<?php

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Basket\Infrastructure\Repository\BasketRepository;
use MaarchCourrier\Basket\Infrastructure\Repository\RedirectBasketRepository;
use MaarchCourrier\Basket\Infrastructure\Service\BasketClauseService;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\Group\Infrastructure\Repository\GroupRepository;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\SignatureBook\Application\Action\Checker\SignatureBookActionPermissionChecker;
use MaarchCourrier\SignatureBook\Application\Action\InterruptCircuitAction;
use MaarchCourrier\SignatureBook\Application\Resource\CreateAnnotatedResourceVersion;
use MaarchCourrier\SignatureBook\Application\Resource\UpdateResourceContentInSignatoryBook;
use MaarchCourrier\SignatureBook\Application\Resource\UseCase\CreateAnnotatedVersionAndSynchroInSignatoryBook;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurSignatureService;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\VisaWorkflowRepository;
use MaarchCourrier\SignatureBook\Infrastructure\Service\CreateVersionService;
use MaarchCourrier\SignatureBook\Infrastructure\Service\MaarchParapheurResourceService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;

class InterruptCircuitActionFactory
{
    /**
     * @return InterruptCircuitAction
     * @throws Exception
     */
    public static function create(): InterruptCircuitAction
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

        $createVersionService = new CreateVersionService();

        $createVersionResource = new CreateAnnotatedResourceVersion(
            $mainResourceRepository,
            $attachmentRepository,
            $createVersionService
        );

        $currentUser = new CurrentUserInformations();
        $updateResourceInSignatoryBook = new UpdateResourceContentInSignatoryBook(
            new SignatureServiceJsonConfigLoader(),
            new MaarchParapheurResourceService(),
            $currentUser
        );

        $createVersionResourceAndSynchroSignatoryBook = new CreateAnnotatedVersionAndSynchroInSignatoryBook(
            $createVersionResource,
            $updateResourceInSignatoryBook
        );

        return new InterruptCircuitAction(
            new CurrentUserInformations(),
            $mainResourceRepository,
            $attachmentRepository,
            new MaarchParapheurSignatureService(),
            new SignatureServiceJsonConfigLoader(),
            new SignatureBookActionPermissionChecker(
                new BasketRepository(),
                new BasketClauseService(),
                new GroupRepository(),
                $mainResourceRepository,
                new VisaWorkflowRepository(new UserRepository()),
                new RedirectBasketRepository()
            ),
            $createVersionResourceAndSynchroSignatoryBook
        );
    }
}
