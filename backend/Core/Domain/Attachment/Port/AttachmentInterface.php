<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Attachment\Port;

use DateTimeImmutable;
use MaarchCourrier\Attachment\Domain\AttachmentType;
use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\Entity\Port\EntityInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\Template\Port\TemplateInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\DocumentStorage\Domain\Document;

interface AttachmentInterface
{
    /**
     * @return int
     */
    public function getResId(): int;

    public function setResId(int $resId): self;

    /**
     * @return MainResourceInterface
     */
    public function getMainResource(): MainResourceInterface;

    /**
     * @return string|null
     */
    public function getTitle(): ?string;

    public function getChrono(): ?string;

    public function getDocument(): Document;

    public function setDocument(Document $document): self;

    /**
     * @return UserInterface
     */
    public function getTypist(): UserInterface;

    /**
     * @return int
     */
    public function getRelation(): int;

    public function getType(): AttachmentType;

    public function setType(AttachmentType $type): self;

    /**
     * @return string
     */
    public function getTypeIdentifier(): string;

    /**
     * @return string
     */
    public function getTypeLabel(): string;

    /**
     * @return bool
     */
    public function isSignable(): bool;

    /**
     * @return string|null
     */
    public function getFilename(): ?string;

    /**
     * @return string|null
     */
    public function getFileFormat(): ?string;

    public function getFingerprint(): ?string;

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

    public function setRecipient(ContactInterface|UserInterface|null $recipient): self;

    public function getRecipient(): ContactInterface|UserInterface|null;

    /**
     * @return string|null
     */
    public function getRecipientType(): ?string;

    /**
     * @return string
     */
    public function getStatus(): string;

    public function getOriginId(): ?int;

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

    public function getSignaturePositions(): array;

    public function getSignaturePositionBySequence(int $sequence): array;

    public function setIsAnnotated(bool $isAnnotated): self;

    public function isAnnotated(): bool;

    public function getExternalId(): array;

    public function setExternalId(array $value): self;

    public function getDestinationFromMainResource(): ?EntityInterface;
}
