<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Shipping Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Infrastructure\Maileva\Controller;

use Exception;
use MaarchCourrier\Contact\Infrastructure\Service\AfnorContactService;
use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\ExternalExport\Application\Maileva\MailevaConfiguration;
use MaarchCourrier\ExternalExport\Application\Maileva\MailevaContactExporter;
use MaarchCourrier\ExternalExport\Application\Maileva\MailevaEreGetSendersList;
use MaarchCourrier\ExternalExport\Application\Maileva\MailevaDownloadProofFile;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaConfigNotFoundProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaCouldNotGetAuthTokenProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaIsDisabledProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaTemplateNotFoundProblem;
use MaarchCourrier\ExternalExport\Infrastructure\Maileva\Repository\MailevaTemplateRepository;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaShippingNotFoundProblem;
use MaarchCourrier\ExternalExport\Infrastructure\Maileva\Repository\ShippingRepository;
use MaarchCourrier\ExternalExport\Infrastructure\Maileva\Service\MailevaApiService;
use Slim\Psr7\Request;
use SrcCore\controllers\LogsController;
use SrcCore\controllers\PasswordController;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;

class MailevaController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     * @throws MailevaTemplateNotFoundProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws Exception
     */
    public function getMailevaEreSenders(Request $request, Response $response, array $args): Response
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $configurationRepository = new ConfigurationRepository();

        $queryParams = $request->getQueryParams();
        $mailevaAuthentication = [];
        if (!empty($queryParams['accountId']) && !empty($queryParams['accountPassword'])) {
            $mailevaAuthentication['id'] = $queryParams['accountId'];
            $mailevaAuthentication['password'] = PasswordController::encrypt([
                'dataToEncrypt' => $queryParams['accountPassword']
            ]);
        }

        $afnorContactService = new AfnorContactService();
        $mailevaContactExporter = new MailevaContactExporter(
            $logger,
            $afnorContactService
        );

        $templateId = null;
        if (!empty($queryParams['templateId'])) {
            $templateId = (int)$queryParams['templateId'];
        }

        $senders = (new MailevaEreGetSendersList(
            new MailevaConfiguration($configurationRepository),
            new MailevaApiService($logger, $mailevaContactExporter),
            new MailevaTemplateRepository()
        ))->execute(
            $templateId,
            $mailevaAuthentication
        );

        return $response->withJson($senders);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     * @throws MailevaShippingNotFoundProblem
     * @throws Exception
     */
    public function downloadDepositProof(Request $request, Response $response, array $args): Response
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $configurationRepository = new ConfigurationRepository();
        $afnorContactService = new AfnorContactService();
        $mailevaContactExporter = new MailevaContactExporter(
            $logger,
            $afnorContactService
        );

        $filename = 'depositProof_' . $args['sendingId'] . "_" . $args['recipientId'] . '.pdf';
        $document = (new MailevaDownloadProofFile(
            new MailevaConfiguration($configurationRepository),
            new MailevaApiService($logger, $mailevaContactExporter),
            new ShippingRepository(new MailevaTemplateRepository())
        ))->downloadDepositProof($args['sendingId'], $args['recipientId']);

        return $response->withJson([
            'encodedDocument' => $document,
            'filename'        => $filename
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     * @throws MailevaShippingNotFoundProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     * @throws Exception
     */
    public function downloadProofOfReceipt(Request $request, Response $response, array $args): Response
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        $configurationRepository = new ConfigurationRepository();
        $afnorContactService = new AfnorContactService();
        $mailevaContactExporter = new MailevaContactExporter(
            $logger,
            $afnorContactService
        );

        $filename = 'proofOfReceipt_' . $args['sendingId'] . "_" . $args['recipientId'] . '.pdf';
        $document = (new MailevaDownloadProofFile(
            new MailevaConfiguration($configurationRepository),
            new MailevaApiService($logger, $mailevaContactExporter),
            new ShippingRepository(new MailevaTemplateRepository())
        ))->downloadProofOfReceipt($args['sendingId'], $args['recipientId']);

        return $response->withJson([
            'encodedDocument' => $document,
            'filename'        => $filename
        ]);
    }
}
