<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Entity Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Entity\Domain;

use JsonSerializable;
use MaarchCourrier\Core\Domain\Entity\Port\EntityInterface;

class Entity implements EntityInterface, JsonSerializable
{
    private int $id;
    private string $entityId;
    private string $entityLabel;
    private string $shortLabel;
    private string $entityType;
    private bool $enabled;
    private string $parentEntityId;

    /**
     * @param  array  $array
     * @return EntityInterface
     */
    public static function createFromArray(array $array = []): EntityInterface
    {
        return (new Entity())
            ->setId($array['id'] ?? 1)
            ->setEntityId($array['entity_id'])
            ->setEntityLabel($array['entity_label'])
            ->setShortLabel($array['short_label'] ?? null)
            ->setEntityType($array['entity_type'] ?? null)
            ->setEnabled($array['enabled'] ?? true)
            ->setParentEntityId($array['parent_entity_id'] ?? true);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int  $id
     * @return EntityInterface
     */
    public function setId(int $id): EntityInterface
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * @param  string  $entityId
     * @return EntityInterface
     */
    public function setEntityId(string $entityId): EntityInterface
    {
        $this->entityId = $entityId;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityLabel(): string
    {
        return $this->entityLabel;
    }

    /**
     * @param  string  $entityLabel
     * @return EntityInterface
     */
    public function setEntityLabel(string $entityLabel): EntityInterface
    {
        $this->entityLabel = $entityLabel;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShortLabel(): ?string
    {
        return $this->shortLabel;
    }

    /**
     * @param  string|null  $shortLabel
     * @return EntityInterface
     */
    public function setShortLabel(?string $shortLabel): EntityInterface
    {
        $this->shortLabel = $shortLabel;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param  bool  $enabled
     * @return EntityInterface
     */
    public function setEnabled(bool $enabled): EntityInterface
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getParentEntityId(): ?string
    {
        return $this->parentEntityId;
    }

    /**
     * @param  string|null  $parentEntityId
     * @return EntityInterface
     */
    public function setParentEntityId(?string $parentEntityId): EntityInterface
    {
        $this->parentEntityId = $parentEntityId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    /**
     * @param  string|null  $entityType
     * @return EntityInterface
     */
    public function setEntityType(?string $entityType): EntityInterface
    {
        $this->entityType = $entityType;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id'               => $this->getId(),
            'entity_id'        => $this->getEntityId(),
            'entity_label'     => $this->getEntityLabel(),
            'short_label'      => $this->getShortLabel(),
            'enabled'          => $this->isEnabled(),
            'parent_entity_id' => $this->getParentEntityId(),
            'entity_type'      => $this->getEntityType()
        ];
    }
}
