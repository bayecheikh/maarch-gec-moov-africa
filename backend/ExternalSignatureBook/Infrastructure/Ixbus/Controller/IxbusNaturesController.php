<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Get Natures Controller class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Ixbus\Controller;

use MaarchCourrier\ExternalSignatureBook\Application\Ixbus\RetrieveConfig;
use MaarchCourrier\ExternalSignatureBook\Application\Ixbus\RetrieveNatureDetailsById;
use MaarchCourrier\ExternalSignatureBook\Application\Ixbus\RetrieveNaturesByInstance;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\CouldNotGetIxbusEditorUsersFromNatureApiServiceProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\CouldNotGetIxbusNatureInfoFromApiServiceProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\CouldNotGetIxbusNaturesFromApiServiceProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Problem\IxbusInstanceIdIsNotDefinedProblem;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\ExternalSignatureBookConfigService;
use MaarchCourrier\ExternalSignatureBook\Infrastructure\Ixbus\Service\IxbusApiService;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class IxbusNaturesController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws IxbusInstanceIdIsNotDefinedProblem
     * @throws CouldNotGetIxbusNaturesFromApiServiceProblem
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        $retrieveNatures = new RetrieveNaturesByInstance(
            new RetrieveConfig(
                new ExternalSignatureBookConfigService()
            ),
            new IxbusApiService()
        );
        return $response->withJson($retrieveNatures->getNatures($args['instanceId'] ?? null));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws CouldNotGetIxbusEditorUsersFromNatureApiServiceProblem
     * @throws CouldNotGetIxbusNatureInfoFromApiServiceProblem
     */
    public function getById(Request $request, Response $response, array $args): Response
    {
        $instanceId = $request->getQueryParams()['instanceId'] ?? null;
        $retrieveNatureById = new RetrieveNatureDetailsById(
            new RetrieveConfig(
                new ExternalSignatureBookConfigService()
            ),
            new IxbusApiService()
        );
        $natureInfo = $retrieveNatureById->get($instanceId, $args['natureId']);
        return $response->withJson($natureInfo);
    }
}
