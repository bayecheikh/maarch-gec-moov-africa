<?php

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Infrastructure\Controller;

use MaarchCourrier\Core\Domain\Problem\NewInternalParapheurDisabledProblem;
use MaarchCourrier\SignatureBook\Application\GetSignatureBookServiceVersion;
use MaarchCourrier\SignatureBook\Domain\Problem\CannotGetServiceVersionProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Infrastructure\Factory\GetSignatureBookServiceVersionFactory;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class GetSignatureBookServiceVersionController
{
    public function __construct(
        private ?GetSignatureBookServiceVersion $getSignatureBookServiceVersion = null
    ) {
    }

    /**
     * @throws SignatureBookNoConfigFoundProblem
     * @throws CannotGetServiceVersionProblem
     * @throws NewInternalParapheurDisabledProblem
     */
    public function getVersion(Request $request, Response $response): Response
    {
        if ($this->getSignatureBookServiceVersion === null) {
            $this->getSignatureBookServiceVersion = GetSignatureBookServiceVersionFactory::create();
        }

        return $response->withJson(
            $this->getSignatureBookServiceVersion->execute()
        );
    }
}
