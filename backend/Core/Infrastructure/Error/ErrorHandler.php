<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ErrorHandler class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Core\Infrastructure\Error;

use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\Problem\InternalServerProblem;
use MaarchCourrier\Core\Domain\Problem\Problem;
use MaarchCourrier\Core\Infrastructure\Environment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Logger;
use SrcCore\http\Response;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private ?EnvironmentInterface $environnement = null,
        private ?LoggerInterface $logger = null
    ) {
        if ($this->environnement === null) {
            $this->environnement = new Environment();
        }

        $this->logger = $logger ?: $this->getDefaultLogger();
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $response = new Response();

        $debug = $this->environnement->isDebug();

        if ($exception instanceof Problem) {
            $problem = $exception;
        } else {
            $problem = new InternalServerProblem($exception, $debug);
        }

        $payload = $problem->jsonSerialize($debug);

        if ($logErrors) {
            $this->writeToErrorLog($problem, $payload);
        }

        return $response
            ->withStatus($problem->getStatus())
            ->withJson($payload);
    }

    /**
     * Returns a default logger implementation.
     */
    private function getDefaultLogger(): LoggerInterface
    {
        return new Logger();
    }

    private function writeToErrorLog(Throwable $throwable, array $throwableContext): void
    {
        $this->logger->error($throwable->getMessage(), $throwableContext);
    }
}
