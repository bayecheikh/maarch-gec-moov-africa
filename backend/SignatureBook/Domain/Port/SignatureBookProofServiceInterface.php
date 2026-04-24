<?php

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

interface SignatureBookProofServiceInterface
{
    public function setConfig(SignatureBookServiceConfig $config): SignatureBookProofServiceInterface;

    public function retrieveProofFileFromApi(int $documentId, string $accessToken, string $mode): array;
}
