<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurlService class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Infrastructure\Curl;

use Exception;
use MaarchCourrier\Core\Domain\Curl\CurlRequest;
use MaarchCourrier\Core\Domain\Curl\CurlResponse;
use MaarchCourrier\Core\Domain\Port\CurlServiceInterface;
use SrcCore\models\CurlModel;

class CurlService implements CurlServiceInterface
{
    /**
     * @throws Exception
     */
    public function call(CurlRequest $curlRequest): CurlRequest
    {
        $bearerAuth = empty($curlRequest->getAuthBearer()) ? null : ['token' => $curlRequest->getAuthBearer()];
        $body = (empty($curlRequest->getBody()) ? null
            : (is_array($curlRequest->getBody()))) ? json_encode($curlRequest->getBody())
            : $curlRequest->getBody();

        $params = [
            'url'        => $curlRequest->getUrl(),
            'method'     => $curlRequest->getMethod(),
            'headers'    => $curlRequest->getHeaders(),
            'basicAuth'  => $curlRequest->getBasicAuth(),
            'bearerAuth' => $bearerAuth,
            'options'    => $curlRequest->getOptions(),
            'body'       => $body
        ];

        $response = CurlModel::exec($params);

        if (!empty($response['response'])) {
            $responseContent = $response['response'];
        } elseif (!empty($response['errors'])) {
            $responseContent = ['errors' => $response['errors']];
        } else {
            $responseContent = $response['raw'];
        }

        $curlResponse = new CurlResponse($response['code'], $responseContent);
        $curlRequest->setCurlResponse($curlResponse);

        return $curlRequest;
    }
}
