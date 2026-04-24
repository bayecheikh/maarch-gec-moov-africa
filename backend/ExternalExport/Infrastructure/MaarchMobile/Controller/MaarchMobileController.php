<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maarch Mobile Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Infrastructure\MaarchMobile\Controller;

use Exception;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\ExternalExport\Application\MaarchMobile\LinkToken;
use MaarchCourrier\ExternalExport\Application\MaarchMobile\UnlinkPreferenceNotification;
use MaarchCourrier\ExternalExport\Application\MaarchMobile\UnlinkTokens;
use MaarchCourrier\History\Application\AddHistoryRecord;
use MaarchCourrier\History\Infrastructure\Repository\HistoryRepository;
use MaarchCourrier\Notification\Infrastructure\Service\NotificationsEventsService;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use Slim\Psr7\Request;
use SrcCore\controllers\LogsController;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;

class MaarchMobileController
{
    /**
     * @throws UserDoesNotExistProblem
     * @throws Exception
     */
    public function linkToken(Request $request, Response $response, array $args): Response
    {
        if (empty($args['id'] ?? null)) {
            return $response->withStatus(400)->withJson(['errors' => 'id value is missing in the url']);
        }

        $body = $request->getParsedBody();

        if (empty($body['mcm'])) {
            return $response->withStatus(400)->withJson(['errors' => 'mcm is missing']);
        }

        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        (new LinkToken(
            $logger,
            new AddHistoryRecord($logger, new HistoryRepository(), new NotificationsEventsService()),
            new UserRepository(),
            new CurrentUserInformations()
        ))->execute($args['id'], $body['mcm']['tokens'] ?? [], $body['mcm']['preferenceNotificationMCM'] ?? []);

        return $response->withStatus(204);
    }

    /**
     * @throws UserDoesNotExistProblem
     * @throws Exception
     */
    public function unLinkToken(Request $request, Response $response, array $args): Response
    {
        if (empty($args['id'] ?? null)) {
            return $response->withStatus(400)->withJson(['errors' => 'id value is missing in the url']);
        }

        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        (new UnlinkTokens(
            $logger,
            new AddHistoryRecord($logger, new HistoryRepository(), new NotificationsEventsService()),
            new UserRepository(),
            new CurrentUserInformations()
        ))->execute($args['id']);

        return $response->withStatus(204);
    }

    /**
     * @throws UserDoesNotExistProblem
     * @throws Exception
     */
    public function unlinkPreferenceNotification(Request $request, Response $response, array $args): Response
    {
        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');

        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            CoreConfigModel::getCustomId()
        );

        (new UnlinkPreferenceNotification(
            $logger,
            new AddHistoryRecord($logger, new HistoryRepository(), new NotificationsEventsService()),
            new UserRepository(),
            new CurrentUserInformations()
        ))->execute($args['id']);

        return $response->withStatus(204);
    }
}
