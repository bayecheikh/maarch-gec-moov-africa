<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\MainResource\Port;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\Entity\Port\EntityInterface;
use MaarchCourrier\Core\Domain\Template\Port\TemplateInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\DocumentStorage\Domain\Document;

interface MainResourceInterface
{
    /**
     * @return int
     */
    public function getResId(): int;

    public function setResId(int $resId): self;

    /**
     * @return string|null
     */
    public function getSubject(): ?string;

    public function setSubject(?string $subject): self;

    /**
     * @return UserInterface
     */
    public function getTypist(): UserInterface;

    public function setTypist(UserInterface $typist): self;

    /**
     * @return string|null
     */
    public function getChrono(): ?string;

    public function setChrono(?string $chrono): self;

    public function getDocument(): Document;

    public function setDocument(Document $document): self;

    public function getFilename(): ?string;

    public function getFingerprint(): ?string;

    /**
     * @return string|null
     */
    public function getFileFormat(): ?string;

    /**
     * @return bool|null
     */
    public function isInSignatureBook(): ?bool;

    public function isInMailevaShipping(): bool;

    /**
     * @return int|null
     */
    public function getExternalDocumentId(): ?int;

    /**
     * @param int|null $externalDocumentId
     * @return self
     */
    public function setExternalDocumentId(?int $externalDocumentId): self;

    public function getExternalState(): array;

    public function setExternalState(array $externalState): self;

    public function getVersion(): int;

    public function setVersion(int $version): self;

    public function getCreationDate(): DateTimeImmutable;

    public function setCreationDate(DateTimeImmutable $date): self;

    public function getModificationDate(): DateTimeImmutable;

    public function setModificationDate(DateTimeImmutable $date): self;

    public function getHasDigitalSignature(): ?bool;

    public function setHasDigitalSignature(?bool $hasDigitalSignature): self;

    public function getTemplate(): ?TemplateInterface;

    public function setTemplate(?TemplateInterface $template): self;

    public function getVersions(): array;

    public function setVersions(array $versions): self;

    public function setSignaturePositions(?array $signature_positions): self;

    public function getSignaturePositions(): array;

    public function getSignaturePositionBySequence(int $sequence): array;

    public function setIsAnnotated(bool $isAnnotated): self;

    public function isAnnotated(): bool;

    public function getExternalId(): array;

    public function setExternalId(array $value): self;

    public function getDestination(): ?EntityInterface;

    public function setDestination(?EntityInterface $entity): self;
}
