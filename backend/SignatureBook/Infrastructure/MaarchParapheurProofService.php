<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MaarchParapheurProofService class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure;

use Exception;
use MaarchCourrier\Core\Domain\Curl\CurlRequest;
use MaarchCourrier\Core\Infrastructure\Curl\CurlService;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookProofServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookCurlRequestErrorProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

class MaarchParapheurProofService implements SignatureBookProofServiceInterface
{
    private SignatureBookServiceConfig $config;

    public function setConfig(SignatureBookServiceConfig $config): SignatureBookProofServiceInterface
    {
        $this->config = $config;
        return $this;
    }


    /**
     * @param int $documentId
     * @param string $accessToken
     * @param string $mode
     * @return array
     * @throws SignatureBookCurlRequestErrorProblem
     * @throws Exception
     */
    public function retrieveProofFileFromApi(int $documentId, string $accessToken, string $mode = 'base64'): array
    {
        $urlGetProofFile = rtrim($this->config->getUrl(), '/') . '/rest/documents/' . $documentId .
            '/proof?mode=' . $mode . '&onlyProof=true';

        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $urlGetProofFile,
            'method'     => 'GET',
            'authBearer' => $accessToken
        ]);

        $curlService = new CurlService();
        $curlRequest = $curlService->call($curlRequest);

        if ($curlRequest->getCurlResponse()->getHttpCode() >= 400) {
            throw new SignatureBookCurlRequestErrorProblem(
                $curlRequest->getCurlResponse()->getHttpCode(),
                $curlRequest->getCurlResponse()->getContentReturn()
            );
        }

        return $curlRequest->getCurlResponse()->getContentReturn();
    }
}
