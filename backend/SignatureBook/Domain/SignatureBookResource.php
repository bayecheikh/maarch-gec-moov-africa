<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureBookResource class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain;

use DateTimeInterface;
use JsonSerializable;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookResourceInterface;

class SignatureBookResource implements SignatureBookResourceInterface, JsonSerializable
{
    private MainResourceInterface|AttachmentInterface $resource;
    private bool $isConverted = false;
    private bool $canModify = false;
    private bool $canDelete = false;
    private bool $isAnnotated = false;

    /**
     * @param MainResourceInterface $mainResource
     * @return SignatureBookResource
     */
    public static function createFromMainResource(MainResourceInterface $mainResource): SignatureBookResource
    {
        return (new SignatureBookResource())
            ->setResource($mainResource);
    }

    /**
     * @param AttachmentInterface $attachment
     * @return SignatureBookResource
     */
    public static function createFromAttachment(AttachmentInterface $attachment): SignatureBookResource
    {
        return (new SignatureBookResource())
            ->setResource($attachment);
    }

    /**
     * @param AttachmentInterface[] $attachments
     * @return SignatureBookResource[]
     */
    public static function createFromAttachments(array $attachments): array
    {
        $resources = [];
        foreach ($attachments as $attachment) {
            $resources[] = self::createFromAttachment($attachment);
        }
        return $resources;
    }

    /**
     * @return MainResourceInterface|AttachmentInterface
     */
    public function getResource(): MainResourceInterface|AttachmentInterface
    {
        return $this->resource;
    }

    /**
     * @param MainResourceInterface|AttachmentInterface $resource
     * @return $this
     */
    public function setResource(MainResourceInterface|AttachmentInterface $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return int
     */
    public function getResId(): int
    {
        return $this->resource->getResId();
    }

    /**
     * @return int|null
     */
    public function getResIdMaster(): ?int
    {
        return $this->resource instanceof AttachmentInterface ? $this->resource->getMainResource()->getResId() : null;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->resource instanceof AttachmentInterface ?
            $this->resource->getTitle() : $this->resource->getSubject();
    }

    /**
     * @return string|null
     */
    public function getChrono(): ?string
    {
        return $this->resource->getChrono();
    }

    /**
     * @return UserInterface
     */
    public function getCreator(): UserInterface
    {
        return $this->resource->getTypist();
    }

    /**
     * @return int|null
     */
    public function getSignedResId(): ?int
    {
        return $this->resource instanceof AttachmentInterface ? $this->resource->getRelation() : null;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->resource instanceof AttachmentInterface ? $this->resource->getTypeIdentifier() : 'main_document';
    }

    /**
     * @return string
     */
    public function getTypeLabel(): string
    {
        return $this->resource instanceof AttachmentInterface ? $this->resource->getTypeLabel() : 'Document Principal';
    }

    /**
     * @return bool
     */
    public function isConverted(): bool
    {
        return $this->isConverted;
    }

    /**
     * @param bool $isConverted
     * @return SignatureBookResource
     */
    public function setIsConverted(bool $isConverted): self
    {
        $this->isConverted = $isConverted;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCanModify(): bool
    {
        return $this->canModify;
    }

    /**
     * @param bool $canModify
     *
     * @return SignatureBookResource
     */
    public function setCanModify(bool $canModify): self
    {
        $this->canModify = $canModify;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCanDelete(): bool
    {
        return $this->canDelete;
    }

    /**
     * @param bool $canDelete
     * @return SignatureBookResource
     */
    public function setCanDelete(bool $canDelete): self
    {
        $this->canDelete = $canDelete;
        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalFormat(): string
    {
        return $this->resource->getDocument()->getFileExtension();
    }

    /**
     * @return int|null
     */
    public function getExternalDocumentId(): ?int
    {
        return $this->resource->getExternalDocumentId();
    }

    /**
     * @return bool
     */
    public function hasDigitalSignature(): bool
    {
        return $this->resource->getHasDigitalSignature() ?? false;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->resource->getVersion();
    }

    /**
     * @return string
     */
    public function getCreationDate(): string
    {
        return $this->resource->getCreationDate()->format(DateTimeInterface::ATOM);
    }

    /**
     * @return string
     */
    public function getModificationDate(): string
    {
        return $this->resource->getModificationDate()->format(DateTimeInterface::ATOM);
    }

    /**
     * @return array
     */
    private function getSignaturePositons(): array
    {
        return $this->resource->getSignaturePositions();
    }

    /**
     * @return array
     */
    public function getVersions(): array
    {
        return $this->resource->getVersions();
    }

    public function isAnnotated(): bool
    {
        return $this->isAnnotated;
    }

    public function setIsAnnotated(bool $isAnnotated): self
    {
        $this->isAnnotated = $isAnnotated;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'resId'               => $this->getResId(),
            'resIdMaster'         => $this->getResIdMaster(),
            'title'               => $this->getTitle(),
            'chrono'              => $this->getChrono(),
            'creator'             => [
                'id'    => $this->getCreator()->getId(),
                'label' => $this->getCreator()->getFullName(),
            ],
            'signedResId'         => $this->getSignedResId(),
            'type'                => $this->getType(),
            'typeLabel'           => $this->getTypeLabel(),
            'isConverted'         => $this->isConverted(),
            'canModify'           => $this->isCanModify(),
            'canDelete'           => $this->isCanDelete(),
            'externalDocumentId'  => $this->getExternalDocumentId(),
            'hasDigitalSignature' => $this->hasDigitalSignature(),
            'originalFormat'      => $this->getOriginalFormat(),
            'version'             => $this->getVersion(),
            'creationDate'        => $this->getCreationDate(),
            'modificationDate'    => $this->getModificationDate(),
            'signaturePositions'  => $this->getSignaturePositons(),
            'versions'            => $this->getVersions(),
            'isAnnotated'         => $this->isAnnotated()
        ];
    }
}
