<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurlRequest class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Curl;

use JsonSerializable;

class CurlRequest implements JsonSerializable
{
    private string $url = "";
    private string $method = "";
    private array $headers = [];
    private array $basicAuth = [];
    private ?string $authBearer = null;
    private array $options = [];
    private array|string $body = [];
    private ?CurlResponse $curlResponse;

    public function createFromArray(array $array = []): CurlRequest
    {
        $request = new CurlRequest();

        $request->setUrl($array['url']);
        $request->setMethod($array['method']);
        $request->setHeaders($array['headers'] ?? []);
        $request->setBasicAuth($array['basicAuth'] ?? []);
        (!empty($array['authBearer'])) ? $request->setAuthBearer($array['authBearer']) : $request->setAuthBearer(null);
        $request->setOptions($array['options'] ?? []);
        (!empty($array['body'])) ? $request->setBody($array['body']) : $request->setBody([]);

        return $request;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return array{}|array{user: string, password: string}
     */
    public function getBasicAuth(): array
    {
        return $this->basicAuth;
    }

    /**
     * @param array{user?: string, password?: string} $auth
     * @return void
     */
    public function setBasicAuth(array $auth): void
    {
        if (isset($auth['user'])) {
            $this->basicAuth['user'] = $auth['user'];
        }

        if (isset($this->basicAuth['user']) && isset($auth['password'])) {
            $this->basicAuth['password'] = $auth['password'];
        }
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getBody(): array|string
    {
        return $this->body;
    }

    public function setBody(array|string $body): void
    {
        $this->body = $body;
    }

    public function getAuthBearer(): ?string
    {
        return $this->authBearer;
    }

    public function setAuthBearer(?string $authBearer): void
    {
        $this->authBearer = $authBearer;
    }

    public function getCurlResponse(): ?CurlResponse
    {
        return $this->curlResponse;
    }

    public function setCurlResponse(?CurlResponse $curlResponse): void
    {
        $this->curlResponse = $curlResponse;
    }

    public function jsonSerialize(): array
    {
        return [
            'url'      => $this->getUrl(),
            'method'   => $this->getMethod(),
            'options'  => $this->getOptions(),
            'body'     => $this->getBody(),
            'response' => $this->getCurlResponse()
        ];
    }
}
