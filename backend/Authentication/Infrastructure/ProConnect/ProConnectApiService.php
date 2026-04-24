<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ProConnect Api Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Infrastructure\ProConnect;

use Exception;
use MaarchCourrier\Authentication\Domain\ProConnect\Port\ProConnectApiServiceInterface;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\ProConnectCouldNotGenerateTokenProblem;
use MaarchCourrier\Authentication\Domain\ProConnect\Problem\ProConnectCouldNotRetrieveUserInfosProblem;
use Psr\Log\LoggerInterface;
use SrcCore\controllers\PasswordController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;

class ProConnectApiService implements ProConnectApiServiceInterface
{
    private string $connectionUrl;
    private string $clientId;
    private string $clientSecret;
    private ?string $authenticationCode = null;
    private ?string $accessToken = null;
    private ?string $nonce = null;
    private ?string $idToken = null;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param array $proConnectConfig
     * @param string $authenticationCode
     * @param string $nonce
     * @return void
     * @throws Exception
     */
    public function setConfig(
        array $proConnectConfig,
        string $authenticationCode,
        string $nonce
    ): void {
        $this->connectionUrl = $proConnectConfig['connectionUrl'];
        $parsedUrl = parse_url($this->connectionUrl);
        if (!isset($parsedUrl['scheme'])) {
            $this->connectionUrl = 'https://' . $proConnectConfig['connectionUrl'];
        }
        $this->clientId = PasswordController::decrypt(['encryptedData' => $proConnectConfig['clientId']]);
        $this->clientSecret = PasswordController::decrypt(['encryptedData' => $proConnectConfig['clientSecret']]);
        $this->authenticationCode = $authenticationCode;
        $this->nonce = $nonce;
    }

    /**
     * @return void
     * @throws ProConnectCouldNotGenerateTokenProblem
     * @throws Exception
     */
    public function generateToken(): void
    {
        $data = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => CoreConfigModel::getApplicationUrl() . "/dist/index.html",
            'code'          => $this->authenticationCode
        ];

        $body = http_build_query($data);

        $curlResponse = CurlModel::exec([
            'url'     => "$this->connectionUrl/api/v2/token",
            'headers' => ['Content-Type: application/x-www-form-urlencoded'],
            'method'  => 'POST',
            'body'    => $body
        ]);

        if ($curlResponse['code'] != 200) {
            $this->logger->error(
                'ProConnect authentication failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );

            throw new ProConnectCouldNotGenerateTokenProblem($curlResponse['errors'], $curlResponse['code']);
        }

        $this->accessToken = $curlResponse['response']['access_token'];
        $this->idToken = $curlResponse['response']['id_token'];
    }

    /**
     * @return bool
     */
    public function isTokenValid(): bool
    {
        [$header64, $payload64, $signature64] = explode('.', $this->idToken);
        $headerJson = base64_decode(strtr($header64, '-_', '+/')); // décodage Base64-URL
        $header = json_decode($headerJson, true);
        $algo = $header['alg'] ?? 'inconnu';

        if ($algo === 'RS256') {
            $payload = json_decode(base64_decode($payload64), true);
            return ($payload['nonce'] == $this->nonce);
        }
        return false;
    }

    /**
     * @return array
     * @throws ProConnectCouldNotRetrieveUserInfosProblem
     * @throws Exception
     */
    public function getUserInfos(): array
    {
        $curlResponse = CurlModel::exec([
            'url'        => "$this->connectionUrl/api/v2/userinfo",
            'bearerAuth' => ['token' => $this->accessToken],
            'headers'    => ['Accept: application/jwt'],
            'method'     => 'GET',
        ]);

        if ($curlResponse['code'] != 200) {
            $this->logger->error(
                'ProConnect authentication failed',
                ['httpCode' => $curlResponse['code'], 'errors' => $curlResponse['errors']]
            );

            throw new ProConnectCouldNotRetrieveUserInfosProblem($curlResponse['errors'], $curlResponse['code']);
        }


        $jwtUserInfos = $curlResponse['response'];

        [$header64, $payload64, $signature64] = explode('.', $jwtUserInfos);
        $headerJson = base64_decode(strtr($header64, '-_', '+/')); // décodage Base64-URL
        $header = json_decode($headerJson, true);
        $algo = $header['alg'] ?? 'inconnu';
        if ($algo === 'RS256') {
            return json_decode(base64_decode($payload64), true);
        } else {
            throw new ProConnectCouldNotRetrieveUserInfosProblem("Unsupported algorithm '$algo'", 400);
        }
    }

    public function getIdToken(): ?string
    {
        return $this->idToken;
    }
}
