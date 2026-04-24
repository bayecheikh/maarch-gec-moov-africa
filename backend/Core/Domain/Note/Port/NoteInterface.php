<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Note Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Note\Port;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface NoteInterface
{
    public function getId(): int;
    public function setId(int $id): self;
    public function getIdentifier(): int;
    public function setIdentifier(int $mainDocumentId): self;
    public function getCreator(): UserInterface;
    public function setCreator(UserInterface $creator): self;
    public function getCreationDate(): DateTimeImmutable;
    public function setCreationDate(DateTimeImmutable $dateTime): self;
    public function getNoteText(): string;
    public function setNoteText(string $text): self;
}
