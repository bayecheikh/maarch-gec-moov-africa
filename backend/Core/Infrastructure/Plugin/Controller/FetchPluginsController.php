<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Fetch Plugins Controller class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Infrastructure\Plugin\Controller;

use MaarchCourrier\Core\Application\Plugin\RetrievePlugin;
use MaarchCourrier\Core\Domain\Plugin\Problem\CouldNotFindPluginProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\Core\Infrastructure\Plugin\Service\PluginsManagerService;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class FetchPluginsController
{
    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws CouldNotFindPluginProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     */
    public function get(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $pluginName = $queryParams['pluginId'] ?? null;

        $license = (new RetrievePlugin(
            new Environment(),
            new PluginsManagerService()
        ))->get($pluginName);

        return $response->withStatus(200)->withJson($license);
    }
}
