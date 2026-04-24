<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Book Workflow Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Service;

use Exception;
use MaarchCourrier\SignatureBook\Domain\Port\Workflow\SignatureBookWorkflowServiceInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;
use SrcCore\models\CurlModel;

class SignatureBookWorkflowService implements SignatureBookWorkflowServiceInterface
{
    private SignatureBookServiceConfig $config;

    /**
     * @param SignatureBookServiceConfig $config
     *
     * @return void
     */
    public function setConfig(SignatureBookServiceConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * @param SignatureBookResource $resource
     *
     * @return bool|array
     * @throws Exception
     */
    public function doesWorkflowExists(SignatureBookResource $resource): bool|array
    {
        $url = rtrim($this->config->getUrl(), '/') . "/rest/documents/{$resource->getExternalDocumentId()}/workflow";
        $response = CurlModel::exec([
            'url'       => $url,
            'basicAuth' => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'method'    => 'GET',
            'headers'   => [
                'content-type: application/json',
                'Accept: application/json',
            ]
        ]);

        if ($response['code'] === 200) {
            return true;
        } else {
            return [
                'code' => $response['code'],
                'error' => $response['errors']
            ];
        }
    }

    /**
     * @param SignatureBookResource $resource
     * @param array $workflow
     *
     * @return bool|array
     * @throws Exception
     */
    public function updateWorkflow(SignatureBookResource $resource, array $workflow): bool|array
    {
        $response = CurlModel::exec([
            'url'       => rtrim($this->config->getUrl(), '/') .
                "/rest/documents/{$resource->getExternalDocumentId()}/workflow",
            'basicAuth' => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'method'    => 'PUT',
            'headers'   => [
                'content-type: application/json',
                'Accept: application/json',
            ],
            'body'      => json_encode($workflow),
        ]);

        if ($response['code'] === 200) {
            return true;
        } else {
            return [
                'code' => $response['code'],
                'error' => $response['response']['errors']
            ];
        }
    }

    /**
     * @param SignatureBookResource $resource
     *
     * @return bool|array
     * @throws Exception
     */
    public function interruptWorkflow(SignatureBookResource $resource): bool|array
    {
        $response = CurlModel::exec([
            'url'       => rtrim($this->config->getUrl(), '/') .
                "/rest/documents/{$resource->getExternalDocumentId()}/workflows/interrupt",
            'basicAuth' => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'method'    => 'PUT',
            'headers'   => [
                'Accept: application/json',
            ]
        ]);

        if ($response['code'] === 200) {
            return true;
        } else {
            return [
                'code' => $response['code'],
                'error' => $response['errors']
            ];
        }
    }

    /**
     * @param SignatureBookResource $resource
     *
     * @return bool|array
     * @throws Exception
     */
    public function deleteResource(SignatureBookResource $resource): bool|array
    {
        $response = CurlModel::exec([
            'url'         => rtrim($this->config->getUrl(), '/') .
                "/rest/documents/{$resource->getExternalDocumentId()}",
            'queryParams' => ['physicalPurge' => 'true'],
            'basicAuth'   => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'method'      => 'DELETE',
            'headers'     => [
                'Accept: application/json',
            ]
        ]);

        if ($response['code'] === 204) {
            return true;
        } else {
            return [
                'code' => $response['code'],
                'error' => $response['errors']
            ];
        }
    }
}
