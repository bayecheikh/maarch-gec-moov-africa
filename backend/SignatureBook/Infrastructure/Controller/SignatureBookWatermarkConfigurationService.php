<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Watermark Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Controller;

use MaarchCourrier\Core\Domain\Curl\CurlRequest;
use MaarchCourrier\Core\Domain\Port\CurlServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookCurlRequestErrorProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;
use MaarchCourrier\SignatureBook\Domain\Watermark\Port\SignatureBookWatermarkConfigurationServiceInterface;

class SignatureBookWatermarkConfigurationService implements SignatureBookWatermarkConfigurationServiceInterface
{
    public function __construct(
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader,
        private readonly CurlServiceInterface $curlService
    ) {
    }

    private SignatureBookServiceConfig $config;

    /**
     * @return SignatureBookWatermarkConfigurationServiceInterface
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function loadConfig(): SignatureBookWatermarkConfigurationServiceInterface
    {
        $signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();

        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->config = $signatureBook;

        return $this;
    }

    /**
     * @throws SignatureBookCurlRequestErrorProblem
     */
    public function fetch(): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'       => rtrim($this->config->getUrl(), '/') . '/rest/customization/electronicWatermark',
            'method'    => 'GET',
            'basicAuth' => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'headers'   => [
                'Accept: application/json'
            ]
        ]);

        $curlRequest = $this->curlService->call($curlRequest);

        if ($curlRequest->getCurlResponse()->getHttpCode() >= 400) {
            throw new SignatureBookCurlRequestErrorProblem(
                $curlRequest->getCurlResponse()->getHttpCode(),
                $curlRequest->getCurlResponse()->getContentReturn()
            );
        }

        return $curlRequest->getCurlResponse()->getContentReturn();
    }

    /**
     * @throws SignatureBookCurlRequestErrorProblem
     */
    public function update(array $config): void
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'       => rtrim($this->config->getUrl(), '/') . '/rest/customization/electronicWatermark',
            'method'    => 'PUT',
            'basicAuth' => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'headers'   => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            'body'      => $config
        ]);

        $curlRequest = $this->curlService->call($curlRequest);

        if ($curlRequest->getCurlResponse()->getHttpCode() >= 400) {
            throw new SignatureBookCurlRequestErrorProblem(
                $curlRequest->getCurlResponse()->getHttpCode(),
                $curlRequest->getCurlResponse()->getContentReturn()
            );
        }
    }
}
