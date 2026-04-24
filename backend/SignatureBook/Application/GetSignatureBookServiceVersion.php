<?php

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Application;

use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\Problem\NewInternalParapheurDisabledProblem;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceVersionDenormalizerInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\CannotGetServiceVersionProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceVersion;

class GetSignatureBookServiceVersion
{
    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader,
        private readonly SignatureServiceInterface $signatureService,
        private readonly SignatureServiceVersionDenormalizerInterface $signatureServiceVersionDenormalizer,
    ) {
    }

    /**
     * @throws SignatureBookNoConfigFoundProblem
     * @throws CannotGetServiceVersionProblem
     * @throws NewInternalParapheurDisabledProblem
     */
    public function execute(): SignatureBookServiceVersion
    {
        if (!$this->environment->isNewInternalParapheurEnabled()) {
            throw new NewInternalParapheurDisabledProblem();
        }

        $config = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($config === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }

        $version = $this->signatureService
            ->setConfig($config)
            ->getVersion();

        return $this->signatureServiceVersionDenormalizer->getSignatureServiceVersion($version);
    }
}
