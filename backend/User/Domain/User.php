<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\User\Domain;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\UserMode;

class User implements UserInterface
{
    private int $id;
    private array $externalId = [];
    private string $firstname;
    private string $lastname;
    private string $mail;
    private string $login;
    private string $phone;
    private array $signatureSubstitutes = [];
    private UserMode $mode;
    private array $longTimeToken = [];
    private string $status;

    /**
     * Create User from an array
     *
     * @param array $array
     * @return UserInterface
     */
    public static function createFromArray(array $array = []): UserInterface
    {
        return (new User())
            ->setId($array['id'] ?? 0)
            ->setExternalId(json_decode($array['external_id'] ?? '[]', true))
            ->setFirstname($array['firstname'] ?? '')
            ->setLastname($array['lastname'] ?? '')
            ->setMail($array['mail'] ?? '')
            ->setLogin($array['login'] ?? $array['user_id'] ?? '')
            ->setPhone($array['phone'] ?? '')
            ->setSignatureSubstitutes(json_decode($array['signature_substitutes'] ?? '[]', true))
            ->setMode(UserMode::tryFrom($array['mode'] ?? null) ?? UserMode::STANDARD)
            ->setLongTimeToken(json_decode($array['long_time_token'] ?? '[]', true))
            ->setStatus($array['status'] ?? 'OK');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id): User
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return array
     */
    public function getExternalId(): array
    {
        return $this->externalId;
    }

    /**
     * @param array $externalId
     * @return $this
     */
    public function setExternalId(array $externalId): User
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname ?? null;
    }

    /**
     * @param string $firstname
     * @return User
     */
    public function setFirstname(string $firstname): User
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname ?? null;
    }

    /**
     * @param string $lastname
     * @return User
     */
    public function setLastname(string $lastname): User
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getFullName(): ?string
    {
        return ($this->firstname ?? null) . ' ' . ($this->lastname ?? null);
    }

    public function getMail(): ?string
    {
        return $this->mail ?? null;
    }

    /**
     * @param string $mail
     * @return User
     */
    public function setMail(string $mail): User
    {
        $this->mail = $mail;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @param string $login
     * @return User
     */
    public function setLogin(string $login): User
    {
        $this->login = $login;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone ?? null;
    }

    /**
     * @param string|null $phone
     * @return User
     */
    public function setPhone(?string $phone): User
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getInternalParapheur(): ?int
    {
        return $this->getExternalId()['internalParapheur'] ?? null;
    }

    /**
     * @return array
     */
    public function getSignatureSubstitutes(): array
    {
        return $this->signatureSubstitutes;
    }

    /**
     * @param array $signatureSubstitutes
     * @return UserInterface
     */
    public function setSignatureSubstitutes(array $signatureSubstitutes): UserInterface
    {
        $this->signatureSubstitutes = $signatureSubstitutes;
        return $this;
    }

    public function getMode(): UserMode
    {
        return $this->mode;
    }

    public function setMode(UserMode $mode): UserInterface
    {
        $this->mode = $mode;
        return $this;
    }

    public function getLongTimeToken(): array
    {
        return $this->longTimeToken;
    }

    public function setLongTimeToken(array $longTimeToken): UserInterface
    {
        $this->longTimeToken = $longTimeToken;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): UserInterface
    {
        $this->status = $status;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'                   => $this->getId(),
            'externalId'           => $this->getExternalId(),
            'firstname'            => $this->getFirstname(),
            'lastname'             => $this->getLastname(),
            'fullName'             => $this->getFullName(),
            'mail'                 => $this->getMail(),
            'login'                => $this->getLogin(),
            'phone'                => $this->getPhone(),
            'signatureSubstitutes' => $this->getSignatureSubstitutes(),
            'mode'                 => $this->getMode()->value,
            'status'               => $this->getStatus()
        ];
    }
}
