<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Update Template class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;
use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagPrivilege;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConsentPageInvalidProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagSignatureProfileInvalidProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagTemplateIdNotFound;

class GoodflagUpdateTemplate
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $configurationRepository,
        private readonly GoodflagCheckTemplate $checkTemplate
    ) {
    }

    /**
     * @param array $body
     * @param string $goodflagTemplateId
     * @return void
     * @throws GoodflagConfigNotFoundProblem
     * @throws GoodflagConsentPageInvalidProblem
     * @throws GoodflagSignatureProfileInvalidProblem
     * @throws GoodflagTemplateIdNotFound
     * @throws ParameterStringCanNotBeEmptyProblem
     */
    public function execute(array $body, string $goodflagTemplateId): void
    {
        if ($this->checkTemplate->checkTemplate($body)) {
            $currentConfig = $this->configurationRepository->getByPrivilege(new GoodflagPrivilege());

            $data = $currentConfig->getValue();
            $templateFound = false;
            foreach ($data['templates'] as $idx => $template) {
                if ($template['id'] === $goodflagTemplateId) {
                    $templateFound = true;

                    $data['templates'][$idx]['label'] = $body['label'];
                    $data['templates'][$idx]['consentPageId'] = $body['consentPageId'];
                    $data['templates'][$idx]['signatureProfileId'] = $body['signatureProfileId'];
                    $data['templates'][$idx]['description'] = $body['description'] ?? '';
                    if (empty($data['templates'][$idx]['description'])) {
                        unset($data['templates'][$idx]['description']);
                    }
                    break;
                }
            }

            if (!$templateFound) {
                throw new GoodflagTemplateIdNotFound($goodflagTemplateId);
            }

            $this->configurationRepository->updateByPrivilege(new GoodflagPrivilege(), $data);
        }
    }
}
