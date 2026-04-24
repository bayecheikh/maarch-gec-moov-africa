<?php

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory;

use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\SignatureBook\Application\GetSignatureBookServiceVersion;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurSignatureService;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceVersionDenormalizer;

class GetSignatureBookServiceVersionFactory
{
    public static function create(): GetSignatureBookServiceVersion
    {
        return new GetSignatureBookServiceVersion(
            new Environment(),
            new SignatureServiceJsonConfigLoader(),
            new MaarchParapheurSignatureService(),
            new SignatureServiceVersionDenormalizer()
        );
    }
}
