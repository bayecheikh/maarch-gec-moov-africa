<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Note class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Note\Domain;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\Note\Port\NoteInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

class Note implements NoteInterface
{
    private int $id;
    private int $identifier;
    private UserInterface $creator;
    private DateTimeImmutable $creationDate;
    private string $noteText;


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): NoteInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function setIdentifier(int $mainDocumentId): NoteInterface
    {
        $this->identifier = $mainDocumentId;
        return $this;
    }

    public function getCreator(): UserInterface
    {
        return $this->creator;
    }

    public function setCreator(UserInterface $creator): NoteInterface
    {
        $this->creator = $creator;
        return $this;
    }

    public function getCreationDate(): DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTimeImmutable $dateTime): NoteInterface
    {
        $this->creationDate = $dateTime;
        return $this;
    }

    public function getNoteText(): string
    {
        return $this->noteText;
    }

    public function setNoteText(string $text): NoteInterface
    {
        $this->noteText = $text;
        return $this;
    }
}
