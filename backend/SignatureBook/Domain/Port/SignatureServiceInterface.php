<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureServiceInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

interface SignatureServiceInterface
{
    /**
     * @param SignatureBookServiceConfig $config
     * @return SignatureServiceInterface
     */
    public function setConfig(SignatureBookServiceConfig $config): SignatureServiceInterface;

    /**
     * @param int $documentId
     * @param string $certificate
     * @param array|null $signatures
     * @param string $accessToken
     * @param string|null $cookieSession
     * @param array|null $mergeData
     * @return array
     */
    public function hashCertificateStep(
        int $documentId,
        string $certificate,
        ?array $signatures,
        string $accessToken,
        ?string $cookieSession,
        ?array $mergeData
    ): array;

    /**
     * @param int $documentId
     * @param string|null $hashSignature
     * @param array|null $signatures
     * @param string|null $certificate
     * @param string|null $signatureContentLength
     * @param string|null $signatureFieldName
     * @param string|null $tmpUniqueId
     * @param string $accessToken
     * @param string|null $cookieSession
     * @param array $resourceToSign
     * @param array|null $mergeData
     * @return array|bool
     */
    public function applySignature(
        int $documentId,
        ?string $hashSignature,
        ?array $signatures,
        ?string $certificate,
        ?string $signatureContentLength,
        ?string $signatureFieldName,
        ?string $tmpUniqueId,
        string $accessToken,
        ?string $cookieSession,
        array $resourceToSign,
        ?array $mergeData
    ): array|bool;

    /**
     * @param string $accessToken
     * @param string $urlRetrieveDoc
     * @return array
     */
    public function retrieveDocumentSign(string $accessToken, string $urlRetrieveDoc): array;

    public function refuseSignature(string $accessToken, int $documentId, ?string $note = null): array|bool;

    public function revertLastStep(string $accessToken, int $documentId): array|bool;

    public function getVersion(): ?array;
}
