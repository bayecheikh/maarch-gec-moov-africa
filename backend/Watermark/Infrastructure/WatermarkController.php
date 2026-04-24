<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

 /**
 * @brief   Watermark Controller
 * @author  dev <dev@maarch.org>
 * @ingroup core
 */

namespace MaarchCourrier\Watermark\Infrastructure;

use MaarchCourrier\Authorization\Domain\Problem\ServiceForbiddenProblem;
use MaarchCourrier\Authorization\Infrastructure\PrivilegeChecker;
use MaarchCourrier\Core\Domain\Problem\InvalidDateFormatProblem;
use MaarchCourrier\Core\Domain\Problem\InvalidHexColorProblem;
use MaarchCourrier\Core\Domain\Problem\InvalidNumericProblem;
use MaarchCourrier\Core\Domain\Problem\InvalidRgbColorArrayProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterCannotBeEmptyProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterIsEmptyOrNotATypeProblem;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\SignatureBook\Infrastructure\Factory\Watermark\CreateOrUpdateSignatureBookWatermarkConfigFactory;
use MaarchCourrier\SignatureBook\Infrastructure\Factory\Watermark\RetrieveSignatureBookWatermarkConfigFactory;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\Watermark\Application\CreateOrUpdateWatermarkConfiguration;
use MaarchCourrier\Watermark\Application\RetrieveWatermarkConfiguration;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class WatermarkController
{
    /**
     * @throws ServiceForbiddenProblem
     */
    public function getWatermarkConfiguration(Request $request, Response $response): Response
    {
        $result = (new RetrieveWatermarkConfiguration(
            new PrivilegeChecker(),
            new CurrentUserInformations(),
            new ConfigurationRepository(),
            new Environment(),
            new RetrieveSignatureBookWatermarkConfigFactory()
        ))->get();

        return $response->withJson($result);
    }

    /**
     * @throws InvalidRgbColorArrayProblem
     * @throws ParameterIsEmptyOrNotATypeProblem
     * @throws InvalidNumericProblem
     * @throws ServiceForbiddenProblem
     * @throws ParameterCannotBeEmptyProblem
     * @throws InvalidDateFormatProblem
     * @throws InvalidHexColorProblem
     */
    public function updateWatermarkConfiguration(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        (new CreateOrUpdateWatermarkConfiguration(
            new PrivilegeChecker(),
            new CurrentUserInformations(),
            new ConfigurationRepository(),
            new Environment(),
            new CreateOrUpdateSignatureBookWatermarkConfigFactory()
        ))->execute($body);

        return $response->withStatus(204);
    }
}
