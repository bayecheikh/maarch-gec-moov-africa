<?php

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Infrastructure;

use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceVersionDenormalizerInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\CannotGetServiceVersionProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceVersion;

class SignatureServiceVersionDenormalizer implements SignatureServiceVersionDenormalizerInterface
{
    public function getSignatureServiceVersion(array $data): SignatureBookServiceVersion
    {
        if (!empty($data['errors'])) {
            throw new CannotGetServiceVersionProblem($data['errors']);
        }

        return new SignatureBookServiceVersion(
            version: $data['version'],
            build: $data['build'],
            time: $data['time']
        );
    }
}
