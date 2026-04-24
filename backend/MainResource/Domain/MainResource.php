<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\MainResource\Domain;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\Entity\Port\EntityInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\SignatureBook\Port\HasSignatureInterface;
use MaarchCourrier\Core\Domain\Template\Port\TemplateInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\DocumentStorage\Domain\Document;

class MainResource implements MainResourceInterface, HasSignatureInterface
{
    private int $resId;
    private ?string $subject;
    private UserInterface $typist;
    private ?string $chrono;
    private Integration $integration;
    private Document $document;
    private ?int $externalDocumentId = null;
    private array $externalState = [];
    private int $version = 1;
    private array $versions = [];
    private DateTimeImmutable $creationDate;
    private DateTimeImmutable $modificationDate;
    private ?TemplateInterface $template;
    private ?array $signaturePositions = [];
    private bool $isAnnotated = false;
    private array $externalId = [];
    private ?EntityInterface $destination = null;

    /**
     * @return int
     */
    public function getResId(): int
    {
        return $this->resId;
    }

    /**
     * @param int $resId
     * @return $this
     */
    public function setResId(int $resId): MainResource
    {
        $this->resId = $resId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param string|null $subject
     * @return $this
     */
    public function setSubject(?string $subject): MainResource
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return UserInterface
     */
    public function getTypist(): UserInterface
    {
        return $this->typist;
    }

    /**
     * @param UserInterface $typist
     * @return $this
     */
    public function setTypist(UserInterface $typist): MainResource
    {
        $this->typist = $typist;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getChrono(): ?string
    {
        return $this->chrono;
    }

    /**
     * @param string|null $chrono
     * @return $this
     */
    public function setChrono(?string $chrono): MainResource
    {
        $this->chrono = $chrono;
        return $this;
    }

    /**
     * @return Integration
     */
    public function getIntegration(): Integration
    {
        return $this->integration;
    }

    /**
     * @param Integration $integration
     * @return $this
     */
    public function setIntegration(Integration $integration): MainResource
    {
        $this->integration = $integration;
        return $this;
    }

    /**
     * @return Document
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    /**
     * @param Document $document
     * @return $this
     */
    public function setDocument(Document $document): MainResource
    {
        $this->document = $document;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->document->getFileName();
    }

    /**
     * @return string|null
     */
    public function getFingerprint(): ?string
    {
        return $this->document->getFingerprint();
    }

    /**
     * @return string|null
     */
    public function getFileFormat(): ?string
    {
        return $this->document->getFileExtension();
    }

    /**
     * @return bool|null
     */
    public function isInSignatureBook(): ?bool
    {
        return $this->integration->getInSignatureBook();
    }

    /**
     * @return bool
     */
    public function isInMailevaShipping(): bool
    {
        return $this->integration->isInMailevaShipping();
    }

    /**
     * @return int|null
     */
    public function getExternalDocumentId(): ?int
    {
        return $this->externalDocumentId;
    }

    /**
     * @param int|null $externalDocumentId
     * @return $this
     */
    public function setExternalDocumentId(?int $externalDocumentId): MainResource
    {
        $this->externalDocumentId = $externalDocumentId;
        return $this;
    }

    public function getExternalState(): array
    {
        return $this->externalState;
    }

    public function setExternalState(array $externalState): MainResource
    {
        $this->externalState = $externalState;
        return $this;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return MainResourceInterface
     */
    public function setVersion(int $version): MainResourceInterface
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreationDate(): DateTimeImmutable
    {
        return $this->creationDate;
    }

    /**
     * @param DateTimeImmutable $date
     *
     * @return MainResourceInterface
     */
    public function setCreationDate(DateTimeImmutable $date): MainResourceInterface
    {
        $this->creationDate = $date;
        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getModificationDate(): DateTimeImmutable
    {
        return $this->modificationDate;
    }

    /**
     * @param DateTimeImmutable $date
     *
     * @return MainResourceInterface
     */
    public function setModificationDate(DateTimeImmutable $date): MainResourceInterface
    {
        $this->modificationDate = $date;
        return $this;
    }

    public function getHasStampSignature(): ?bool
    {
        return $this->externalState['hasStampSignature'] ?? false;
    }

    public function getHasDigitalSignature(): ?bool
    {
        return $this->externalState['hasDigitalSignature'] ?? false;
    }

    public function setHasDigitalSignature(?bool $hasDigitalSignature): MainResource
    {
        $this->externalState['hasDigitalSignature'] = $hasDigitalSignature ?? false;
        return $this;
    }

    public function getTemplate(): ?TemplateInterface
    {
        return $this->template;
    }

    public function setTemplate(?TemplateInterface $template): MainResourceInterface
    {
        $this->template = $template;
        return $this;
    }

    public function getVersions(): array
    {
        return $this->versions;
    }

    public function setVersions(array $versions): MainResource
    {
        $this->versions = $versions;
        return $this;
    }

    public function getSignaturePositions(): array
    {
        return $this->signaturePositions;
    }

    /**
     * @param array|null $signature_positions
     * @return $this
     */
    public function setSignaturePositions(?array $signature_positions): MainResource
    {
        $this->signaturePositions = $signature_positions;
        return $this;
    }

    public function getSignaturePositionBySequence(int $sequence): array
    {
        $signaturePositions = $this->getSignaturePositions();
        foreach ($signaturePositions as $position) {
            if ($position['sequence'] === $sequence) {
                return $position;
            }
        }
        return [];
    }

    public function setIsAnnotated(bool $isAnnotated): MainResource
    {
        $this->isAnnotated = $isAnnotated;
        return $this;
    }

    public function isAnnotated(): bool
    {
        return $this->isAnnotated;
    }

    public function getExternalId(): array
    {
        return $this->externalId;
    }

    public function setExternalId(array $value): MainResource
    {
        $this->externalId = $value;
        return $this;
    }

    public function getDestination(): ?EntityInterface
    {
        return $this->destination;
    }

    public function setDestination(?EntityInterface $entity): MainResource
    {
        $this->destination = $entity;
        return $this;
    }
}
