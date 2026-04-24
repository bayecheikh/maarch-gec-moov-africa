<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Note Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Note\Domain\Port;

use MaarchCourrier\Core\Domain\Note\Port\NoteInterface;

interface NoteRepositoryInterface
{
    /**
     * @param int[] $ids
     *
     * @return NoteInterface[]
     */
    public function getNotesByIds(array $ids): array;
    public function add(NoteInterface $note): int;
    public function delete(NoteInterface $note): void;
}
