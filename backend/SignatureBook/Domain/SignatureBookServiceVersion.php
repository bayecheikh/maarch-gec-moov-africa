<?php

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Domain;

use JsonSerializable;

class SignatureBookServiceVersion implements JsonSerializable
{
    public function __construct(
        public readonly string $version,
        public readonly string $build,
        public readonly string $time,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'version' => $this->version,
            'build'   => $this->build,
            'time'    => $this->time,
        ];
    }
}
