<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief DocServer Type
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Domain;

class DocServerType
{
    private string $docserverTypeId;
    private ?string $docserverTypeLabel = null;
    private bool $enabled = true;
    private ?string $fingerprintMode = null;

    public function getDocserverTypeId(): string
    {
        return $this->docserverTypeId;
    }

    public function setDocserverTypeId(string $docserverTypeId): self
    {
        $this->docserverTypeId = $docserverTypeId;
        return $this;
    }

    public function getDocserverTypeLabel(): ?string
    {
        return $this->docserverTypeLabel;
    }

    public function setDocserverTypeLabel(?string $docserverTypeLabel): self
    {
        $this->docserverTypeLabel = $docserverTypeLabel;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getFingerprintMode(): ?string
    {
        return $this->fingerprintMode;
    }

    public function setFingerprintMode(?string $fingerprintMode): self
    {
        $this->fingerprintMode = $fingerprintMode;
        return $this;
    }
}
