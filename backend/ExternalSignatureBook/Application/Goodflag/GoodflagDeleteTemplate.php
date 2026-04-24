<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Delete Template class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagPrivilege;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagTemplateIdNotFound;

class GoodflagDeleteTemplate
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $configurationRepository
    ) {
    }

    /**
     * @param string $goodflagTemplateId
     * @return void
     * @throws GoodflagConfigNotFoundProblem
     * @throws GoodflagTemplateIdNotFound
     */
    public function execute(string $goodflagTemplateId): void
    {
        $currentConfig = $this->configurationRepository->getByPrivilege(new GoodflagPrivilege());
        if (!$currentConfig) {
            throw new GoodflagConfigNotFoundProblem();
        }

        $data = $currentConfig->getValue();
        $templateFound = false;
        $idxToDelete = null;
        foreach ($data['templates'] as $idx => $template) {
            if ($template['id'] === $goodflagTemplateId) {
                $templateFound = true;
                $idxToDelete = $idx;
                break;
            }
        }

        if (!$templateFound) {
            throw new GoodflagTemplateIdNotFound($goodflagTemplateId);
        }

        unset($data['templates'][$idxToDelete]);
        $this->configurationRepository->updateByPrivilege(new GoodflagPrivilege(), $data);
    }
}
