<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Entity Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Entity\Port;

interface EntityInterface
{
    /**
     * @param  array  $array
     * @return EntityInterface
     */
    public static function createFromArray(array $array = []): EntityInterface;

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @param  int  $id
     * @return EntityInterface
     */
    public function setId(int $id): EntityInterface;

    /**
     * @return string
     */
    public function getEntityId(): string;

    /**
     * @param  string  $entityId
     * @return EntityInterface
     */
    public function setEntityId(string $entityId): EntityInterface;

    /**
     * @return string
     */
    public function getEntityLabel(): string;

    /**
     * @param  string  $entityLabel
     * @return EntityInterface
     */
    public function setEntityLabel(string $entityLabel): EntityInterface;

    /**
     * @return string|null
     */
    public function getShortLabel(): ?string;

    /**
     * @param  string|null  $shortLabel
     * @return EntityInterface
     */
    public function setShortLabel(?string $shortLabel): EntityInterface;

    /**
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * @param  bool  $enabled
     * @return EntityInterface
     */
    public function setEnabled(bool $enabled): EntityInterface;

    /**
     * @return string|null
     */
    public function getParentEntityId(): ?string;

    /**
     * @param  string|null  $parentEntityId
     * @return EntityInterface
     */
    public function setParentEntityId(?string $parentEntityId): EntityInterface;

    /**
     * @return string|null
     */
    public function getEntityType(): ?string;

    /**
     * @param  string|null  $entityType
     * @return EntityInterface
     */
    public function setEntityType(?string $entityType): EntityInterface;
}
