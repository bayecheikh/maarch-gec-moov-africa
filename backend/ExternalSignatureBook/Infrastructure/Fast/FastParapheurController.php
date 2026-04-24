<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief FastParapheurController class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Fast;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Authorization\Infrastructure\MainResourcePerimeterCheckerService;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Core\Infrastructure\Curl\CurlService;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\ExternalSignatureBook\Application\Fast\RetrieveConfig;
use MaarchCourrier\ExternalSignatureBook\Application\RetrieveDocumentWorkflow;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\ExternalSignatureBookConfigService;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use Slim\Psr7\Request;
use SrcCore\controllers\LogsController;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;

class FastParapheurController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function getWorkflow(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();

        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');
        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $userRepository = new UserRepository();
        $templateRepository = new TemplateRepository();
        $entityRepository = new EntityRepository();
        $mainRepository = new MainResourceRepository($userRepository, $templateRepository, $entityRepository);
        $contactRepository = new ContactRepository();

        $retrieveConfig = new RetrieveConfig(new ExternalSignatureBookConfigService());

        $result = (new RetrieveDocumentWorkflow(
            new CurrentUserInformations(),
            $mainRepository,
            new MainResourcePerimeterCheckerService(),
            new AttachmentRepository($userRepository, $mainRepository, $templateRepository, $contactRepository),
            new FastApiService($logger, $retrieveConfig, new CurlService())
        ))->getByTypeAndId($queryParams['type'] ?? '', $args['id']);

        return $response->withJson($result);
    }
}
