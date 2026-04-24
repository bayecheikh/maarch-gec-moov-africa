<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Notification Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Notification\Domain;

use MaarchCourrier\Core\Domain\Notification\Port\NotificationInterface;

class Notification implements NotificationInterface
{
    private int $id;
    /**
     * Identifiant de la notification (repris dans les scripts sh)
     */
    private string $stringId;
    private string $description = '';
    private bool $enabled = true;
    private string $eventId;
    /**
     * @deprecated
     */
    private string $mode = '';
    private ?int $templateId = null;
    private string $diffusionType;
    private array $diffusionProperties = [];
    private ?string $attachForType = null;
    private array $attachForProperties = [];
    private bool $sendAsRecap = false;

    public function convertToDbItem(): array
    {
        return [
            'notification_sid'     => $this->getId(),
            'notification_id'      => $this->getStringId(),
            'description'          => $this->getDescription(),
            'is_enabled'           => $this->isEnabled(),
            'event_id'             => $this->getEventId(),
            'notification_mode'    => $this->getMode(),
            'template_id'          => $this->getTemplateId(),
            'diffusion_type'       => $this->getDiffusionType(),
            'diffusion_properties' => $this->getDiffusionProperties(),
            'attachfor_type'       => $this->getAttachForType(),
            'attachfor_properties' => $this->getAttachForProperties(),
            'send_as_recap'        => $this->isSendAsRecap()
        ];
    }

    //Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getStringId(): string
    {
        return $this->stringId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getTemplateId(): ?int
    {
        return $this->templateId;
    }

    public function getDiffusionType(): string
    {
        return $this->diffusionType;
    }

    public function getDiffusionProperties(): array
    {
        return $this->diffusionProperties;
    }

    public function getAttachForType(): ?string
    {
        return $this->attachForType;
    }

    public function getAttachForProperties(): array
    {
        return $this->attachForProperties;
    }

    public function isSendAsRecap(): bool
    {
        return $this->sendAsRecap;
    }

    //Setters
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setStringId(string $stringId): self
    {
        $this->stringId = $stringId;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function setEventId(string $eventId): self
    {
        $this->eventId = $eventId;
        return $this;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    public function setTemplateId(?int $templateId): self
    {
        $this->templateId = $templateId;
        return $this;
    }

    public function setDiffusionType(string $diffusionType): self
    {
        $this->diffusionType = $diffusionType;
        return $this;
    }

    public function setDiffusionProperties(array $diffusionProperties): self
    {
        $this->diffusionProperties = $diffusionProperties;
        return $this;
    }

    public function setAttachForType(?string $attachForType): self
    {
        $this->attachForType = $attachForType;
        return $this;
    }

    public function setAttachForProperties(array $attachForProperties): self
    {
        $this->attachForProperties = $attachForProperties;
        return $this;
    }

    public function setSendAsRecap(bool $sendAsRecap): self
    {
        $this->sendAsRecap = $sendAsRecap;
        return $this;
    }
}
