<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Infrastructure\Repository;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\Email\Domain\Email;
use MaarchCourrier\Email\Domain\EmailStatus;
use MaarchCourrier\Email\Domain\Port\EmailInterface;
use MaarchCourrier\Email\Domain\Port\EmailRepositoryInterface;
use SrcCore\models\DatabaseModel;

class EmailRepository implements EmailRepositoryInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * @param int $id
     *
     * @return EmailInterface|null
     * @throws UserDoesNotExistProblem
     * @throws Exception
     */
    public function getById(int $id): ?EmailInterface
    {
        $email = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['emails'],
            'where'  => ['id = ?'],
            'data'   => [$id],
        ]);

        if (empty($email[0])) {
            return null;
        }

        $sendByUser = $this->userRepository->getUserById($email[0]['user_id']);
        if ($sendByUser == null) {
            throw new UserDoesNotExistProblem();
        }

        return (new Email())
            ->setId($email[0]['id'])
            ->setUser($sendByUser)
            ->setSender(json_decode($email[0]['sender'], true))
            ->setRecipients(json_decode($email[0]['recipients'], true))
            ->setCc(json_decode($email[0]['cc'], true))
            ->setCci(json_decode($email[0]['cci'], true))
            ->setObject($email[0]['object'])
            ->setBody($email[0]['body'] ?? '')
            ->setDocuments(json_decode($email[0]['document'] ?? '[]', true))
            ->setIsHtml($email[0]['is_html'])
            ->setStatus(EmailStatus::from($email[0]['status']))
            ->setMessageExchangeId($email[0]['message_exchange_id'])
            ->setCreationDate(new DateTimeImmutable($email[0]['creation_date'] ?? ''))
            ->setSendDate(new DateTimeImmutable($email[0]['send_date'] ?? ''));
    }

    /**
     * @throws Exception
     */
    public function updateEmail(EmailInterface $email, array $set): void
    {
        DatabaseModel::update([
            'table' => 'emails',
            'set'   => $set,
            'where' => ['id = ?'],
            'data'  => [$email->getId()]
        ]);
    }

    /**
     * @param EmailInterface $email
     * @return bool
     * @throws Exception
     */
    public function doesEmailExist(EmailInterface $email): bool
    {
        $emailExist = DatabaseModel::select([
            'select' => ['*'],
            'table'  => ['emails'],
            'where'  => [
                'user_id = ?',
                "sender->>'email' = ?",
                "recipients->>0 = ?",
                'object = ?',
                'body = ?',
                'status in (?)'
            ],
            'data'   => [
                $email->getUser()->getId(),
                $email->getSender()['email'],
                $email->getRecipients()[0],
                $email->getObject(),
                $email->getBody(),
                ['SENT', $email->getStatus()->value]
            ]
        ]);

        return !empty($emailExist);
    }
}
