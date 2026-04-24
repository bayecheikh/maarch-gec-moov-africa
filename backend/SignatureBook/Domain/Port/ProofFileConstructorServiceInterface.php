<?php

namespace MaarchCourrier\SignatureBook\Domain\Port;

interface ProofFileConstructorServiceInterface
{
    public function createZip(array $docsToZip): array;
    public function makeXmlFromArray(array $array, string $rootElement = "root"): string;
    public function makePdfFromArray(array $data): string;
}
