<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Note Repository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Note\Infrastructure\Repository;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\Core\Domain\Note\Port\NoteInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\Note\Domain\Note;
use MaarchCourrier\Note\Domain\Port\NoteRepositoryInterface;
use Note\models\NoteModel;
use SrcCore\models\DatabaseModel;

class NoteRepository implements NoteRepositoryInterface
{
    public function __construct(public UserRepositoryInterface $userRepository)
    {
    }

    /**
     * @param int[] $ids
     *
     * @return NoteInterface[]
     * @throws Exception
     */
    public function getNotesByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $databaseNotes = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['notes'],
            'where'  => ['id in (?)'],
            'data'   => [$ids],
        ]);

        if (empty($databaseNotes)) {
            return [];
        }

        $notes = [];
        foreach ($databaseNotes as $note) {
            $creator = $this->userRepository->getUserById($note['user_id']);
            if (empty($creator)) {
                throw new UserDoesNotExistProblem();
            }

            $notes[] = (new Note())
                ->setId($note['id'])
                ->setIdentifier($note['identifier'])
                ->setCreator($creator)
                ->setCreationDate(new DateTimeImmutable($note['creation_date']))
                ->setNoteText($note['note_text']);
        }

        return $notes;
    }

    /**
     * @param NoteInterface $note
     * @return int
     * @throws Exception
     */
    public function add(NoteInterface $note): int
    {
        return NoteModel::create([
            'resId'     => $note->getIdentifier(),
            'user_id'   => $note->getCreator()->getId(),
            'note_text' => $note->getNoteText()
        ]);
    }

    /**
     * @throws Exception
     */
    public function delete(NoteInterface $note): void
    {
        NoteModel::delete([
            'where' => ['id = ?'],
            'data'  => [$note->getId()]
        ]);
    }
}
