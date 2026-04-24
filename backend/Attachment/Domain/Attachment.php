<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Attachment\Domain;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\Core\Domain\Entity\Port\EntityInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\SignatureBook\Port\HasSignatureInterface;
use MaarchCourrier\Core\Domain\Template\Port\TemplateInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\DocumentStorage\Domain\Document;

class Attachment implements AttachmentInterface, HasSignatureInterface
{
    private int $resId;
    private MainResourceInterface $mainResource;
    private ?string $title;
    private ?string $chrono;
    private UserInterface $typist;
    private int $relation;
    private AttachmentType $type;
    private Document $document;
    private ?int $externalDocumentId = null;
    private array $externalState = [];
    private ContactInterface|UserInterface|null $recipient = null;
    private string $status;
    private ?int $originId;
    private int $version = 1;
    private array $versions = [];
    private DateTimeImmutable $creationDate;
    private DateTimeImmutable $modificationDate;
    private ?TemplateInterface $template;
    private ?array $signaturePositions = [];
    private bool $isAnnotated = false;
    private array $externalId = [];

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
    public function setResId(int $resId): Attachment
    {
        $this->resId = $resId;
        return $this;
    }

    /**
     * @return MainResourceInterface
     */
    public function getMainResource(): MainResourceInterface
    {
        return $this->mainResource;
    }

    /**
     * @param MainResourceInterface $mainResource
     * @return $this
     */
    public function setMainResource(MainResourceInterface $mainResource): Attachment
    {
        $this->mainResource = $mainResource;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return $this
     */
    public function setTitle(?string $title): Attachment
    {
        $this->title = $title;
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
    public function setChrono(?string $chrono): Attachment
    {
        $this->chrono = $chrono;
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
    public function setTypist(UserInterface $typist): Attachment
    {
        $this->typist = $typist;
        return $this;
    }

    /**
     * @return int
     */
    public function getRelation(): int
    {
        return $this->relation;
    }

    /**
     * @param int $relation
     * @return $this
     */
    public function setRelation(int $relation): Attachment
    {
        $this->relation = $relation;
        return $this;
    }

    /**
     * @return AttachmentType
     */
    public function getType(): AttachmentType
    {
        return $this->type;
    }

    /**
     * @param AttachmentType $type
     * @return $this
     */
    public function setType(AttachmentType $type): Attachment
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeIdentifier(): string
    {
        return $this->type->getType();
    }

    /**
     * @return string
     */
    public function getTypeLabel(): string
    {
        return $this->type->getLabel();
    }

    /**
     * @return bool
     */
    public function isSignable(): bool
    {
        return $this->type->isSignable();
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
    public function setDocument(Document $document): Attachment
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
    public function getFileFormat(): ?string
    {
        return $this->document->getFileExtension();
    }

    /**
     * @return string|null
     */
    public function getFingerprint(): ?string
    {
        return $this->document->getFingerprint();
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
    public function setExternalDocumentId(?int $externalDocumentId): Attachment
    {
        $this->externalDocumentId = $externalDocumentId;
        return $this;
    }

    public function getExternalState(): array
    {
        return $this->externalState;
    }

    public function setExternalState(array $externalState): Attachment
    {
        $this->externalState = $externalState;
        return $this;
    }

    public function setRecipient(ContactInterface|UserInterface|null $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getRecipient(): ContactInterface|UserInterface|null
    {
        return $this->recipient;
    }

    /**
     * @return string|null
     */
    public function getRecipientType(): ?string
    {
        if ($this->recipient instanceof ContactInterface) {
            return 'contact';
        } elseif ($this->recipient instanceof UserInterface) {
            return 'user';
        } else {
            return null;
        }
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
     * @return Attachment
     */
    public function setVersion(int $version): Attachment
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): Attachment
    {
        $this->status = $status;
        return $this;
    }

    public function getOriginId(): ?int
    {
        return $this->originId;
    }

    public function setOriginId(?int $originId): Attachment
    {
        $this->originId = $originId;
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
     * @return Attachment
     */
    public function setCreationDate(DateTimeImmutable $date): Attachment
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
     * @return Attachment
     */
    public function setModificationDate(DateTimeImmutable $date): Attachment
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

    public function setHasDigitalSignature(?bool $hasDigitalSignature): AttachmentInterface
    {
        $this->externalState['hasDigitalSignature'] = $hasDigitalSignature ?? false;
        return $this;
    }

    public function getTemplate(): ?TemplateInterface
    {
        return $this->template;
    }

    public function setTemplate(?TemplateInterface $template): AttachmentInterface
    {
        $this->template = $template;
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
    public function setSignaturePositions(?array $signature_positions): Attachment
    {
        $this->signaturePositions = $signature_positions;
        return $this;
    }

    /**
     * @param int $sequence
     * @return array
     */
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

    /**
     * @return array
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * @param array $versions
     * @return self
     */
    public function setVersions(array $versions): Attachment
    {
        $this->versions = $versions;
        return $this;
    }

    /**
     * @param bool $isAnnotated
     * @return self
     */
    public function setIsAnnotated(bool $isAnnotated): Attachment
    {
        $this->isAnnotated = $isAnnotated;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAnnotated(): bool
    {
        return $this->isAnnotated;
    }

    public function getExternalId(): array
    {
        return $this->externalId;
    }

    public function setExternalId(array $value): self
    {
        $this->externalId = $value;
        return $this;
    }

    public function getDestinationFromMainResource(): ?EntityInterface
    {
        return $this->mainResource->getDestination();
    }
}
