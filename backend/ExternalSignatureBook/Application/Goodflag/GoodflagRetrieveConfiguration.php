<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Goodflag Config class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\ExternalSignatureBookType;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagConfig;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagInstanceConfig;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagPrivilege;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagTemplateIdNotFound;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\ExternalSignatureBookConfigServiceInterface;

class GoodflagRetrieveConfiguration
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $configurationRepository,
        private readonly ExternalSignatureBookConfigServiceInterface $externalSignatureBookConfigService
    ) {
    }

    /**
     * @return GoodflagConfig
     * @throws GoodflagConfigNotFoundProblem
     */
    public function retrieveAccountConfiguration(): GoodflagConfig
    {
        $configuration = $this->configurationRepository->getByPrivilege(new GoodflagPrivilege());
        if ($configuration == null) {
            throw new GoodflagConfigNotFoundProblem();
        }

        $configuration = $configuration->getValue();

        return (new GoodflagConfig())
            ->setIsEnabled(
                $this->externalSignatureBookConfigService->getEnable() === ExternalSignatureBookType::GOODFLAG->value
            )
            ->setUrl($configuration['url'] ?? '')
            ->setAccessToken($configuration['accessToken'] ?? '')
            ->setOptions($configuration['options'] ?? []);
    }

    /**
     * @return array
     * @throws GoodflagConfigNotFoundProblem
     */
    public function retrieveListTemplates(): array
    {
        $configuration = $this->configurationRepository->getByPrivilege(new GoodflagPrivilege());
        if ($configuration == null) {
            throw new GoodflagConfigNotFoundProblem();
        }

        $listGoodflagTemplates = [];
        $configuration = $configuration->getValue();
        if (!empty($configuration['templates'])) {
            foreach ($configuration['templates'] as $template) {
                $listGoodflagTemplates[] = (new GoodflagInstanceConfig())
                    ->setId($template['id'])
                    ->setLabel($template['label'])
                    ->setDescription($template['description'] ?? '')
                    ->setConsentPageId($template['consentPageId'])
                    ->setSignatureProfileId($template['signatureProfileId']);
            }
        }
        return $listGoodflagTemplates;
    }

    /**
     * @param string $id
     * @return GoodflagInstanceConfig
     * @throws GoodflagConfigNotFoundProblem
     * @throws GoodflagTemplateIdNotFound
     */
    public function retrieveTemplateConfiguration(string $id): GoodflagInstanceConfig
    {
        $listGoodflagTemplates = $this->retrieveListTemplates();
        foreach ($listGoodflagTemplates as $template) {
            if ($template->getId() === $id) {
                return $template;
            }
        }

        throw new GoodflagTemplateIdNotFound($id);
    }
}
