<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Update Goodflag Config class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;
use MaarchCourrier\Core\Domain\Problem\InvalidUrlFormatProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagPrivilege;

class GoodflagUpdateConfiguration
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $configurationRepository
    ) {
    }

    /**
     * @param array $body
     * @return void
     * @throws InvalidUrlFormatProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     */
    public function execute(array $body): void
    {
        if (empty($body['url']) || !is_string($body['url'])) {
            throw new ParameterStringCanNotBeEmptyProblem('url');
        }

        if (!filter_var($body['url'], FILTER_VALIDATE_URL)) {
            throw new InvalidUrlFormatProblem($body['url']);
        }

        $configuration = $this->configurationRepository->getByPrivilege(new GoodflagPrivilege());
        if ($configuration == null) {
            if (empty($body['accessToken']) || !is_string($body['accessToken'])) {
                throw new ParameterStringCanNotBeEmptyProblem('accessToken');
            }
            $data = [
                'url'         => $body['url'],
                'accessToken' => $body['accessToken'],
                'options'     => $body['options'] ?? []
            ];
            $this->configurationRepository->createByPrivilege(new GoodflagPrivilege(), $data);
        } else {
            $data = $configuration->getValue();
            $data['url'] = $body['url'];
            $data['accessToken'] = !empty($body['accessToken']) ? $body['accessToken'] : $data['accessToken'];
            $data['options'] = $body['options'] ?? [];

            $this->configurationRepository->updateByPrivilege(new GoodflagPrivilege(), $data);
        }
    }
}
