<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Template Thumbnail Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentConversion\Infrastructure\Controller;

use MaarchCourrier\Core\Domain\Problem\FileTypeIsNotAllowedProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterMustBeGreaterThanZeroException;
use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\DocumentConversion\Application\RetrieveThumbnailOfPdfByPage;
use MaarchCourrier\DocumentConversion\Domain\Problem\ConvertOnePageFromFileContentProblem;
use MaarchCourrier\DocumentConversion\Infrastructure\Service\ConvertThumbnailService;
use MaarchCourrier\DocumentStorage\Domain\Problem\CouldNotGetMimeTypeFromFileContentProblem;
use MaarchCourrier\DocumentStorage\Infrastructure\DocumentStoragePrivilegeChecker;
use MaarchCourrier\DocumentStorage\Infrastructure\Service\FileSystemService;
use Respect\Validation\Validator;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class ConvertPdfToThumbnailController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws ConvertOnePageFromFileContentProblem
     * @throws CouldNotGetMimeTypeFromFileContentProblem
     * @throws FileTypeIsNotAllowedProblem
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ParameterStringCanNotBeEmptyProblem
     */
    public function getFileContentByPage(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        if (!Validator::stringType()->validate($body['base64FileContent'] ?? null)) {
            return $response->withStatus(403)->withJson([
                'errors' => "body 'base64FileContent' is not a string"
            ]);
        }
        if (!Validator::intVal()->validate($args['page'])) {
            return $response->withStatus(403)->withJson(['errors' => "url 'page' param is not an integer"]);
        }

        $result = (new RetrieveThumbnailOfPdfByPage(
            new FileSystemService(),
            new DocumentStoragePrivilegeChecker(),
            new ConvertThumbnailService()
        ))->execute($body['base64FileContent'], $args['page']);

        return $response->withJson($result);
    }
}
