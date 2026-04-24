<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief LaunchLadProcess class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Mercure\Application;

use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\Mercure\Domain\Port\MercureServiceInterface;

class LaunchLadProcess
{
    /**
     * @param  MercureServiceInterface  $mercureService
     */
    public function __construct(
        private readonly MercureServiceInterface $mercureService
    ) {
    }

    public function execute(string $base64FileContent, string $format, ?int $modelId = null): array
    {
        if (empty($base64FileContent)) {
            throw new ParameterStringCanNotBeEmptyProblem('base64FileContent');
        }

        if (empty($format)) {
            throw new ParameterStringCanNotBeEmptyProblem('format');
        }

        if ($this->mercureService->isEnabled($modelId)) {
            return $this->mercureService->processLad($base64FileContent, $format);
        }

        return [];
    }
}
