<?php

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\SignatureBook\Domain\Problem\CannotGetServiceVersionProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceVersion;

interface SignatureServiceVersionDenormalizerInterface
{
    /**
     * @throws CannotGetServiceVersionProblem
     */
    public function getSignatureServiceVersion(array $data): SignatureBookServiceVersion;
}
