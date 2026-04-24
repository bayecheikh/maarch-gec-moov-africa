<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Domain;

use DateTimeImmutable;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Email\Domain\Port\EmailInterface;

class Email implements EmailInterface
{
    private int $id;
    /**
     * @var UserInterface The user who sent the email
     */
    private UserInterface $user;
    private array $sender = [];
    private array $recipients = [];
    private array $cc = [];
    private array $cci = [];
    private ?string $object = null;
    private ?string $body = null;
    private array $documents = [];
    private bool $isHtml = true;
    private EmailStatus $status;
    /**
     * @var int|null M2M info
     */
    private ?int $messageExchangeId = null;
    private DateTimeImmutable $creationDate;
    private ?DateTimeImmutable $sendDate = null;

    public static function createFromArray(array $data): EmailInterface
    {
        return (new Email())
            ->setId($data['id'])
            ->setUser($data['user'])
            ->setSender($data['sender'])
            ->setRecipients($data['recipients'])
            ->setCc($data['cc'])
            ->setCci($data['cci'])
            ->setObject($data['object'] ?? null)
            ->setBody($data['body'] ?? null)
            ->setDocuments($data['documents'] ?? [])
            ->setIsHtml($data['isHtml'] ?? true)
            ->setStatus($data['status'])
            ->setMessageExchangeId($data['messageExchangeId'] ?? null)
            ->setCreationDate($data['creationDate'])
            ->setSendDate($data['sendDate'] ?? null);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): EmailInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): EmailInterface
    {
        $this->user = $user;
        return $this;
    }

    public function getSender(): array
    {
        return $this->sender;
    }

    public function setSender(array $sender): EmailInterface
    {
        $this->sender = $sender;
        return $this;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function setRecipients(array $recipients): EmailInterface
    {
        $this->recipients = array_unique($recipients, SORT_REGULAR);
        return $this;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function setCc(array $cc): EmailInterface
    {
        $this->cc = array_unique($cc, SORT_REGULAR);
        return $this;
    }

    public function getCci(): array
    {
        return $this->cci;
    }

    public function setCci(array $cci): EmailInterface
    {
        $this->cci = array_unique($cci, SORT_REGULAR);
        return $this;
    }

    public function getObject(): string
    {
        return $this->object;
    }

    public function setObject(string $object): EmailInterface
    {
        $this->object = $object;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): EmailInterface
    {
        $this->body = $body;
        return $this;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function setDocuments(array $documents): EmailInterface
    {
        $this->documents = $documents;
        return $this;
    }

    public function isHtml(): bool
    {
        return $this->isHtml;
    }

    public function setIsHtml(bool $isHtml): EmailInterface
    {
        $this->isHtml = $isHtml;
        return $this;
    }

    public function getStatus(): EmailStatus
    {
        return $this->status;
    }

    public function setStatus(EmailStatus $status): EmailInterface
    {
        $this->status = $status;
        return $this;
    }

    public function getMessageExchangeId(): ?int
    {
        return $this->messageExchangeId;
    }

    public function setMessageExchangeId(?int $messageExchangeId): EmailInterface
    {
        $this->messageExchangeId = $messageExchangeId;
        return $this;
    }

    public function getCreationDate(): DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTimeImmutable $creationDate): EmailInterface
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getSendDate(): DateTimeImmutable
    {
        return $this->sendDate;
    }

    public function setSendDate(DateTimeImmutable $sendDate): EmailInterface
    {
        $this->sendDate = $sendDate;
        return $this;
    }
}
