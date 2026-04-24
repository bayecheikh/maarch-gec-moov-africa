<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Signatory Substitute Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Infrastructure\Controller;

use MaarchCourrier\Basket\Infrastructure\Repository\RedirectBasketRepository;
use MaarchCourrier\Core\Domain\Problem\Problem;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\Core\Infrastructure\Database\DatabaseService;
use MaarchCourrier\SignatureBook\Domain\Problem\DeleteSubstituteInSignatoryBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserExternalIdNotFoundProblem;
use MaarchCourrier\SignatureBook\Infrastructure\Factory\User\DeleteSubstituteInSignatoryBookFactory;
use MaarchCourrier\SignatureBook\Infrastructure\Factory\User\AddSubstituteInSignatoryBookFactory;
use MaarchCourrier\User\Application\AddSignatorySubstitute;
use MaarchCourrier\User\Application\DeleteSignatorySubstitute;
use MaarchCourrier\User\Domain\Problem\NotCurrentUserProblem;
use MaarchCourrier\User\Domain\Problem\NotSignatorySubstituteProblem;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use Exception;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use Respect\Validation\Validator;

class SignatorySubstituteController
{
    /**
     * @throws Exception
     */
    public function addSignatorySubstitute(Request $request, Response $response, array $args): Response
    {
        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Id is empty or not an integer']);
        }

        $body = $request->getParsedBody();

        if (!Validator::intVal()->validate($body['destUser'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body param destUser is empty or not an integer']);
        }

        $addSignatorySubstituteAndSynchro = (new AddSignatorySubstitute(
            new UserRepository(),
            new RedirectBasketRepository(),
            new AddSubstituteInSignatoryBookFactory(),
            new DatabaseService(),
            new CurrentUserInformations()
        ));
        $result = $addSignatorySubstituteAndSynchro->execute($args['id'], $body['destUser']);
        return $response->withStatus(200)->withJson($result);
    }

    /**
     * @throws DeleteSubstituteInSignatoryBookProblem
     * @throws SignatureBookNoConfigFoundProblem
     * @throws NotCurrentUserProblem
     * @throws UserExternalIdNotFoundProblem
     * @throws UserDoesNotExistProblem
     * @throws NotSignatorySubstituteProblem
     * @throws Problem
     */
    public function deleteSignatorySubstitute(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Id is empty or not an integer']);
        }

        if (!Validator::notEmpty()->arrayType()->validate($body['destUsers'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body param destUsers is empty or not an array']);
        }

        foreach ($body['destUsers'] as $destUserId) {
            $deleteSignatorySubstituteAndSynchro = (new DeleteSignatorySubstitute(
                new UserRepository(),
                new CurrentUserInformations(),
                new RedirectBasketRepository(),
                new DeleteSubstituteInSignatoryBookFactory(),
                new DatabaseService()
            ));

            $deleteSignatorySubstituteAndSynchro->execute($args['id'], $destUserId);
        }

        return $response->withStatus(204);
    }
}
