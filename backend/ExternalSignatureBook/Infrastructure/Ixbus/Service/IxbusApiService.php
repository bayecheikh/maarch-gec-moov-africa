<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Ixbus Api Service class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure\Ixbus\Service;

use MaarchCourrier\Core\Domain\CurlError;
use MaarchCourrier\Core\Domain\Port\CurlErrorInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Ixbus\Port\IxbusCommonConfigInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\IxbusApiServiceInterface;
use SrcCore\models\CurlModel;

class IxbusApiService implements IxbusApiServiceInterface
{
    public function getNatures(IxbusCommonConfigInterface $config): CurlErrorInterface|array
    {
        $curlResponse = CurlModel::exec([
            'url'     => rtrim($config->getUrl(), '/') . '/api/parapheur/v1/nature',
            'headers' => ['IXBUS_API:' . $config->getTokenAPI()],
            'method'  => 'GET'
        ]);

        if (!empty($curlResponse['response']['error'])) {
            return (new CurlError())
                ->setCode($curlResponse['error']['code'])
                ->setMessage($curlResponse['response']['error']['message']);
        }

        foreach ($curlResponse['response']['payload'] as $key => $value) {
            unset($curlResponse['response']['payload'][$key]['motClefs']);
        }
        return $curlResponse['response']['payload'];
    }

    public function getNatureById(
        IxbusCommonConfigInterface $config,
        string $id
    ): CurlErrorInterface|array {
        $curlResponse = CurlModel::exec([
            'method'  => 'GET',
            'url'     => rtrim($config->getUrl(), '/') . "/api/parapheur/v1/circuit/$id",
            'headers' => ['IXBUS_API:' . $config->getTokenAPI()]
        ]);

        if (empty($curlResponse['response']['payload']) || !empty($curlResponse['response']['error'])) {
            return (new CurlError())
                ->setCode(500)
                ->setMessage($curlResponse['message'] ?? "HTTP {$curlResponse['code']} while contacting ixbus");
        }

        return $curlResponse['response']['payload'];
    }

    public function getEditorUsersFromNatureById(
        IxbusCommonConfigInterface $config,
        string $id
    ): CurlErrorInterface|array {
        $curlResponse = CurlModel::exec([
            'url'     => rtrim($config->getUrl(), '/') . "/api/parapheur/v1/nature/$id/redacteur",
            'headers' => ['IXBUS_API:' . $config->getTokenAPI()],
            'method'  => 'GET'
        ]);

        if (empty($curlResponse['response']['payload']) || !empty($curlResponse['response']['error'])) {
            return (new CurlError())
                ->setCode(500)
                ->setMessage($curlResponse['message'] ?? "HTTP {$curlResponse['code']} while contacting ixbus");
        }

        return $curlResponse['response']['payload'];
    }
}
