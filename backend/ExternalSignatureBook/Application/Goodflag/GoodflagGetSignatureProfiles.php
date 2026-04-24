<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Get Signature Profiles Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;

class GoodflagGetSignatureProfiles
{
    public function __construct(
        private readonly GoodflagApiServiceInterface $goodflagApiService
    ) {
    }

    /**
     * @return array
     * @throws GoodflagConfigNotFoundProblem
     */
    public function execute(): array
    {
        $this->goodflagApiService->loadConfig();
        $signatureProfiles = $this->goodflagApiService->retrieveSignatureProfiles();

        return array_filter($signatureProfiles, fn($profile) => $this->filterSignatureProfiles($profile));
    }

    /**
     * @param array $profile
     * @return bool
     */
    private function filterSignatureProfiles(array $profile): bool
    {
        return (
            !$profile['isDisabled'] &&
            !empty($profile['signatureType']) && $profile['signatureType'] === 'pades'
        );
    }
}
