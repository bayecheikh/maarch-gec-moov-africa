<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Notification\Port;

interface NotificationInterface
{
    public function convertToDbItem(): array;

    public function getId(): int;

    public function setId(int $id): self;

    public function getStringId(): string;

    public function setStringId(string $stringId): self;

    public function getDescription(): string;

    public function setDescription(string $description): self;

    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): self;

    public function getEventId(): string;

    public function setEventId(string $eventId): self;

    /**
     * @deprecated
     */
    public function getMode(): string;

    public function setMode(string $mode): self;

    public function getTemplateId(): ?int;

    public function setTemplateId(?int $templateId): self;

    public function getDiffusionType(): string;

    public function setDiffusionType(string $diffusionType): self;

    public function getDiffusionProperties(): array;

    public function setDiffusionProperties(array $diffusionProperties): self;

    public function getAttachForType(): ?string;

    public function setAttachForType(?string $attachForType): self;

    public function getAttachForProperties(): array;

    public function setAttachForProperties(array $attachForProperties): self;

    public function isSendAsRecap(): bool;

    public function setSendAsRecap(bool $sendAsRecap): self;
}
