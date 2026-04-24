<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Get Consent Pages Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;

class GoodflagGetConsentPages
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
        $consentPages = $this->goodflagApiService->retrieveConsentPages();
        return array_filter($consentPages, fn($page) => $this->filterConsentPages($page));
    }

    /**
     * @param array $consentPages
     * @return bool
     */
    private function filterConsentPages(array $consentPages): bool
    {
        return (
            !$consentPages['isDisabled'] &&
            !empty($consentPages['signingMode']) && in_array($consentPages['signingMode'], ['local', 'server']) &&
            !empty($consentPages['stepType']) && $consentPages['stepType'] === 'signature'
        );
    }
}
