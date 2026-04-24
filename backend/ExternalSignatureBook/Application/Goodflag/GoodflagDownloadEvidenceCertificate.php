<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Download Evidence Certificate class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagWorkflow;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;

class GoodflagDownloadEvidenceCertificate
{
    public function __construct(
        private readonly GoodflagApiServiceInterface $goodflagApiService
    ) {
    }

    /**
     * @param string $workflowId
     * @return string
     * @throws GoodflagConfigNotFoundProblem
     */
    public function execute(string $workflowId): string
    {
        $this->goodflagApiService->loadConfig();
        return $this->goodflagApiService->downloadEvidenceCertificate((new GoodflagWorkflow())->setId($workflowId));
    }
}
