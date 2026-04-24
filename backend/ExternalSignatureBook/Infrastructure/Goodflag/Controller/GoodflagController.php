<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Get Natures Controller class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Controller;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Authorization\Infrastructure\MainResourcePerimeterCheckerService;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Core\Domain\Attachment\AttachmentNotFoundProblem;
use MaarchCourrier\Core\Domain\Problem\InvalidUrlFormatProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterStringMustBeOfValueProblem;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\Core\Infrastructure\Curl\CurlService;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagDeleteTemplate;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagDownloadEvidenceCertificate;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagGetConsentPages;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagGetCorrespondents;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagGetSignatureProfiles;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagRetrieveConfiguration;
use MaarchCourrier\ExternalSignatureBook\Application\Goodflag\GoodflagUpdateConfiguration;
use MaarchCourrier\ExternalSignatureBook\Application\RetrieveDocumentWorkflow;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConsentPageInvalidProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagSignatureProfileInvalidProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagTemplateIdNotFound;
use MaarchCourrier\ExternalSignatureBook\Domain\Problem\ResourceIsNotLinkedToAnExternalSignatureBookProblem;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\ExternalSignatureBookConfigService;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Factory\GoodflagCreateOrUpdateTemplateFactory;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Factory\GoodflagProcessWebhookEventFactory;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Goodflag\Service\GoodflagApiService;
use MaarchCourrier\MainResource\Domain\Problem\MainResourceDoesNotExistProblem;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use Slim\Psr7\Request;
use SrcCore\controllers\LogsController;
use SrcCore\controllers\PasswordController;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;

class GoodflagController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     */
    public function getConfiguration(Request $request, Response $response): Response
    {
        $retrieveGoodflagConfiguration = (new GoodflagRetrieveConfiguration(
            new ConfigurationRepository(),
            new ExternalSignatureBookConfigService()
        ))->retrieveAccountConfiguration();

        $accessToken = $retrieveGoodflagConfiguration->getAccessToken();
        $goodflagConfiguration = $retrieveGoodflagConfiguration->jsonSerialize();
        if (!empty($accessToken)) {
            $retrieveGoodflagConfiguration->setAccessToken("");
            $goodflagConfiguration['accessToken'] = "";
            $goodflagConfiguration['accessTokenAlreadySet'] = true;
        }

        return $response->withJson($goodflagConfiguration);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws InvalidUrlFormatProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     * @throws Exception
     */
    public function updateConfiguration(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        if (!empty($body)) {
            $body['accessToken'] = !empty($body['accessToken'])
                ? PasswordController::encrypt(['dataToEncrypt' => $body['accessToken']])
                : "";

            (new GoodflagUpdateConfiguration(
                new ConfigurationRepository()
            ))->execute($body);
        }

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     */
    public function getTemplates(Request $request, Response $response): Response
    {
        $retrieveGoodflagTemplates = (new GoodflagRetrieveConfiguration(
            new ConfigurationRepository(),
            new ExternalSignatureBookConfigService()
        ))->retrieveListTemplates();
        return $response->withJson($retrieveGoodflagTemplates);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     * @throws GoodflagConsentPageInvalidProblem
     * @throws GoodflagSignatureProfileInvalidProblem
     * @throws GoodflagTemplateIdNotFound
     * @throws ParameterStringCanNotBeEmptyProblem
     * @throws Exception
     */
    public function createTemplate(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        if (!empty($body)) {
            $goodflagCreateTemplate = GoodflagCreateOrUpdateTemplateFactory::create('create');

            $body['webhookEndpoint'] = CoreConfigModel::getApplicationUrl() . '/rest/goodflag/webhook';
            $goodflagCreateTemplate->execute($body);
        }
        return $response->withStatus(201);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     * @throws GoodflagTemplateIdNotFound
     */
    public function getTemplateById(Request $request, Response $response, array $args): Response
    {
        $template = (new GoodflagRetrieveConfiguration(
            new ConfigurationRepository(),
            new ExternalSignatureBookConfigService()
        ))->retrieveTemplateConfiguration($args['id']);
        return $response->withJson($template);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     * @throws GoodflagConsentPageInvalidProblem
     * @throws GoodflagSignatureProfileInvalidProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     * @throws GoodflagTemplateIdNotFound
     */
    public function updateTemplate(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();
        if (!empty($body)) {
            $goodflagCreateTemplate = GoodflagCreateOrUpdateTemplateFactory::create('update');
            $goodflagCreateTemplate->execute($body, $args['id'] ?? null);
        }
        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     * @throws GoodflagTemplateIdNotFound
     */
    public function deleteTemplate(Request $request, Response $response, array $args): Response
    {
        (new GoodflagDeleteTemplate(
            new ConfigurationRepository(),
        ))->execute($args['id']);

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     * @throws Exception
     */
    public function getConsentPages(Request $request, Response $response): Response
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $retrieveGoodflagConsentPages = (new GoodflagGetConsentPages(
            new GoodflagApiService(
                $logger,
                new CurlService(),
                new GoodflagRetrieveConfiguration(
                    new ConfigurationRepository(),
                    new ExternalSignatureBookConfigService()
                )
            )
        ))->execute();
        return $response->withJson($retrieveGoodflagConsentPages);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     * @throws Exception
     */
    public function getSignatureProfiles(Request $request, Response $response): Response
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $retrieveGoodflagSignatureProfiles = (new GoodflagGetSignatureProfiles(
            new GoodflagApiService(
                $logger,
                new CurlService(),
                new GoodflagRetrieveConfiguration(
                    new ConfigurationRepository(),
                    new ExternalSignatureBookConfigService()
                )
            )
        ))->execute();
        return $response->withJson($retrieveGoodflagSignatureProfiles);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     * @throws Exception
     */
    public function getCorrespondents(Request $request, Response $response): Response
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');
        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $queryParam = $request->getQueryParams();

        $retrieveGoodflagCorrespondents = (new GoodflagGetCorrespondents(
            new GoodflagApiService(
                $logger,
                new CurlService(),
                new GoodflagRetrieveConfiguration(
                    new ConfigurationRepository(),
                    new ExternalSignatureBookConfigService()
                )
            )
        ))->execute($queryParam['search'] ?? null);

        return $response->withJson($retrieveGoodflagCorrespondents);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GoodflagConfigNotFoundProblem
     * @throws Exception
     */
    public function processWebhook(Request $request, Response $response): Response
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');
        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $body = $request->getParsedBody();
        if (!empty($body) && isset($body['eventType']) && isset($body['id']) && isset($body['created'])) {
            $goodflagProcessWebhookEvent = GoodflagProcessWebhookEventFactory::create();
            $goodflagProcessWebhookEvent->execute($body['id'], $body['eventType'], $body['created']);
        } else {
            $logger->error('Webhook event not valid');
            if (isset($body['eventType'])) {
                $logger->info('Webhook event type: ' . $body['eventType']);
            } else {
                $logger->error('Webhook event type not set');
            }

            if (isset($body['id'])) {
                $logger->info('Webhook event id: ' . $body['id']);
            } else {
                $logger->error('Webhook event id not set');
            }

            if (isset($body['created'])) {
                $logger->info('Webhook event created: ' . $body['created']);
            } else {
                $logger->error('Webhook event created not set');
            }
        }

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function downloadEvidenceCertificate(Request $request, Response $response, array $args): Response
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $retrieveConfig = new GoodflagRetrieveConfiguration(
            new ConfigurationRepository(),
            new ExternalSignatureBookConfigService()
        );

        $document = (new GoodflagDownloadEvidenceCertificate(
            new GoodflagApiService($logger, new CurlService(), $retrieveConfig)
        ))->execute($args['goodflagWorkflowId']);

        $filename = "evidenceProof_{$args['goodflagWorkflowId']}.pdf";
        return $response->withJson([
            'encodedDocument' => base64_encode($document),
            'filename'        => $filename
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws AttachmentNotFoundProblem
     * @throws ParameterStringMustBeOfValueProblem
     * @throws ResourceIsNotLinkedToAnExternalSignatureBookProblem
     * @throws MainResourceDoesNotExistProblem
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

        $retrieveConfig = new GoodflagRetrieveConfiguration(
            new ConfigurationRepository(),
            new ExternalSignatureBookConfigService()
        );

        $result = (new RetrieveDocumentWorkflow(
            new CurrentUserInformations(),
            $mainRepository,
            new MainResourcePerimeterCheckerService(),
            new AttachmentRepository($userRepository, $mainRepository, $templateRepository, $contactRepository),
            new GoodflagApiService($logger, new CurlService(), $retrieveConfig)
        ))->getByTypeAndId($queryParams['type'] ?? '', $args['id']);

        return $response->withJson($result);
    }
}
