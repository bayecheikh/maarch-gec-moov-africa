<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Check Template class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConsentPageInvalidProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagSignatureProfileInvalidProblem;

class GoodflagCheckTemplate
{
    public function __construct(
        private readonly GoodflagGetSignatureProfiles $getSignatureProfiles,
        private readonly GoodflagGetConsentPages $getConsentPages
    ) {
    }

    /**
     * @throws GoodflagConfigNotFoundProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     * @throws GoodflagSignatureProfileInvalidProblem
     * @throws GoodflagConsentPageInvalidProblem
     */
    public function checkTemplate(array $body): bool
    {
        if (empty($body['label']) || !is_string($body['label'])) {
            throw new ParameterStringCanNotBeEmptyProblem('label');
        }

        if (empty($body['consentPageId']) || !is_string($body['consentPageId'])) {
            throw new ParameterStringCanNotBeEmptyProblem('consentPageId');
        }

        if (empty($body['signatureProfileId']) || !is_string($body['signatureProfileId'])) {
            throw new ParameterStringCanNotBeEmptyProblem('signatureProfileId');
        }

        $consentPages = $this->getConsentPages->execute();
        $consentPageIdValid = false;
        foreach ($consentPages as $consentPage) {
            if ($consentPage['id'] === $body['consentPageId']) {
                $consentPageIdValid = true;
                break;
            }
        }
        if (!$consentPageIdValid) {
            throw new GoodflagConsentPageInvalidProblem($body['consentPageId']);
        }

        $signatureProfiles = $this->getSignatureProfiles->execute();
        $signatureProfileIdValid = false;
        foreach ($signatureProfiles as $signatureProfile) {
            if ($signatureProfile['id'] === $body['signatureProfileId']) {
                $signatureProfileIdValid = true;
                break;
            }
        }

        if (!$signatureProfileIdValid) {
            throw new GoodflagSignatureProfileInvalidProblem($body['signatureProfileId']);
        }

        return true;
    }
}
