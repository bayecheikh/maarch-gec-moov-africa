<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Api Token Controller
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\User\Infrastructure\Controller;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\User\Domain\Problem\ExpirationDateMustBeInTheFutureProblem;
use MaarchCourrier\User\Domain\Problem\TokenExpirationDateExceedProblem;
use MaarchCourrier\User\Domain\Problem\UserHaveAlreadyTokenProblem;
use MaarchCourrier\User\Domain\Problem\UserIsNotInRestModeProblem;
use MaarchCourrier\User\Domain\Problem\UserTokenNotFoundProblem;
use MaarchCourrier\User\Infrastructure\Factory\GetApiTokenActionFactory;
use MaarchCourrier\User\Infrastructure\Factory\RevokeApiTokenActionFactory;
use MaarchCourrier\User\Infrastructure\Factory\UpdateLastUseActionFactory;
use Respect\Validation\Validator;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use MaarchCourrier\User\Infrastructure\Factory\CreateApiTokenFactory;

class ApiTokenController
{
    /**
     * @throws ExpirationDateMustBeInTheFutureProblem
     * @throws TokenExpirationDateExceedProblem
     * @throws UserDoesNotExistProblem
     * @throws UserHaveAlreadyTokenProblem
     * @throws UserIsNotInRestModeProblem
     */
    public function create(Request $request, Response $response, array $args = []): Response
    {
        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not set']);
        }

        $body = $request->getParsedBody();
        if (empty($body['expirationDate'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Missing required fields']);
        }

        $createApiTokenAction = (new CreateApiTokenFactory())->createTokenAction();

        $expirationDate = new DateTimeImmutable($body['expirationDate']);
        $token = $createApiTokenAction->execute((int)$args['id'], $expirationDate);

        return $response->withJson(['token' => $token->toArray()]);
    }

    /**
     * @throws UserDoesNotExistProblem
     */
    public function getToken(Request $request, Response $response, array $args = []): Response
    {
        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not set']);
        }

        $getApiTokenAction = (new GetApiTokenActionFactory())->getApiTokenAction();

        $token = $getApiTokenAction->execute((int)$args['id']);

        return $response->withJson(['token' => !empty($token) ? $token->toArray() : []]);
    }

    /**
     * Revoke the API token for the given user
     *
     * @param Request $request
     * @param Response $response
     * @param array $args The route arguments
     *
     * @return Response
     * @throws Exception
     */
    public function revoke(Request $request, Response $response, array $args = []): Response
    {
        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not set']);
        }

        $revokeApiTokenAction = (new RevokeApiTokenActionFactory())->revokeApiTokenAction();

        $revokeApiTokenAction->execute((int)$args['id']);

        return $response->withStatus(204);
    }

    /**
     * @throws UserDoesNotExistProblem
     * @throws UserTokenNotFoundProblem
     */
    public function updateLastUse(Request $request, Response $response, array $args = []): Response
    {
        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not set']);
        }

        $updateLastUseAction = (new UpdateLastUseActionFactory())->updateLastUseAction();

        $token = $updateLastUseAction->execute((int)$args['id']);

        return $response->withJson(['token' => $token->toArray()]);
    }
}
