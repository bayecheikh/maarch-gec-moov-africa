<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maarch Parapheur Resource Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Service;

use MaarchCourrier\SignatureBook\Domain\Port\MaarchParapheurResourceServiceInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;
use SrcCore\models\CurlModel;

class MaarchParapheurResourceService implements MaarchParapheurResourceServiceInterface
{
    private SignatureBookServiceConfig $config;

    /**
     * @param  SignatureBookServiceConfig  $config
     *
     * @return void
     */
    public function setConfig(SignatureBookServiceConfig $config): void
    {
        $this->config = $config;
    }

    public function updateResourceContent(
        SignatureBookResource $resource,
        string $encodedContent,
        string $accessToken
    ): bool|array {
        $body = [
            'base64FileContent' => $encodedContent
        ];

        $url = rtrim($this->config->getUrl(), '/') .
            "/rest/documents/{$resource->getExternalDocumentId()}/content";

        $response = CurlModel::exec([
            'url'       => $url,
            'method'    => 'PUT',
            'bearerAuth' => ['token' => $accessToken],
            'headers'   => [
                'content-type: application/json',
                'Accept: application/json',
            ],
            'body'      => json_encode($body)
        ]);

        if ($response['code'] === 200) {
            return true;
        } else {
            return [
                'code'  => $response['code'],
                'error' => $response['errors']
            ];
        }
    }
}
