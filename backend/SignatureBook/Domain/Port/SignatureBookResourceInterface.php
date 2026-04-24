<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Signature Book Resource Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface SignatureBookResourceInterface
{
    public static function createFromMainResource(MainResourceInterface $mainResource): SignatureBookResourceInterface;
    public static function createFromAttachment(AttachmentInterface $attachment): SignatureBookResourceInterface;
    public function getResource(): MainResourceInterface|AttachmentInterface;
    public function setResource(MainResourceInterface|AttachmentInterface $resource): SignatureBookResourceInterface;
    public function getResId(): int;
    public function getResIdMaster(): ?int;
    public function getTitle(): string;
    public function getChrono(): ?string;
    public function getCreator(): UserInterface;
    public function getSignedResId(): ?int;
    public function getType(): string;
    public function getTypeLabel(): string;
    public function isConverted(): bool;
    public function setIsConverted(bool $isConverted): SignatureBookResourceInterface;
    public function isCanModify(): bool;
    public function setCanModify(bool $canModify): SignatureBookResourceInterface;
    public function isCanDelete(): bool;
    public function setCanDelete(bool $canDelete): SignatureBookResourceInterface;
    public function getOriginalFormat(): string;
    public function getVersion(): int;
    public function getCreationDate(): string;
    public function getModificationDate(): string;
    public function getVersions(): array;
}
