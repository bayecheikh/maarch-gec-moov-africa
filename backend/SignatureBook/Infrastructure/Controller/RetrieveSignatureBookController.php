<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Signature Book Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Controller;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Authorization\Domain\Problem\MainResourceOutOfPerimeterProblem;
use MaarchCourrier\Authorization\Infrastructure\AttachmentPrivilegeCheckerService;
use MaarchCourrier\Authorization\Infrastructure\MainResourcePerimeterCheckerService;
use MaarchCourrier\Authorization\Infrastructure\PrivilegeChecker;
use MaarchCourrier\Basket\Infrastructure\Repository\BasketRepository;
use MaarchCourrier\Basket\Infrastructure\Repository\GroupBasketRepository;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\DocumentConversion\Infrastructure\Service\ConvertPdfService;
use MaarchCourrier\Group\Infrastructure\Repository\GroupRepository;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\SignatureBook\Application\RetrieveSignatureBook;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\SignatureBookRepository;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\VisaWorkflowRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use SignatureBook\controllers\SignatureBookController;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;

class RetrieveSignatureBookController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     * @throws Exception
     */
    public function getSignatureBook(Request $request, Response $response, array $args): Response
    {
        $env = new Environment();

        if (!$env->isNewInternalParapheurEnabled()) {
            $signatureBookController = new SignatureBookController();
            return $signatureBookController->getSignatureBook($request, $response, $args);
        }

        $templateRepository = new TemplateRepository();
        $userRepository = new UserRepository();
        $mainResourceRepository = new MainResourceRepository(
            $userRepository,
            $templateRepository,
            new EntityRepository()
        );
        $contactRepository = new ContactRepository();

        $basketRepository = new BasketRepository();
        $groupRepository = new GroupRepository();
        $groupBasketRepository = new GroupBasketRepository($groupRepository, $basketRepository);

        $currentUserInformations = new CurrentUserInformations();
        $privilegeChecker = new PrivilegeChecker();
        $visaWorkflowRepository = new VisaWorkflowRepository(new UserRepository());

        $attachmentService = new AttachmentPrivilegeCheckerService(
            $currentUserInformations,
            $privilegeChecker,
            $visaWorkflowRepository
        );

        $retrieve = new RetrieveSignatureBook(
            $mainResourceRepository,
            $currentUserInformations,
            new MainResourcePerimeterCheckerService(),
            new SignatureBookRepository($groupBasketRepository),
            new ConvertPdfService(),
            new AttachmentRepository($userRepository, $mainResourceRepository, $templateRepository, $contactRepository),
            $privilegeChecker,
            $visaWorkflowRepository,
            $attachmentService
        );

        $signatureBook = $retrieve->getSignatureBook($args['resId'], $args['groupId'], $args['basketId']);
        return $response->withJson($signatureBook);
    }
}
