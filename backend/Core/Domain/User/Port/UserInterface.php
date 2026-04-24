<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\User\Port;

use JsonSerializable;
use MaarchCourrier\Core\Domain\User\UserMode;

interface UserInterface extends JsonSerializable
{
    /**
     * Create a user object of an array (keys/values) from the database
     * ```
     * User $user = User::createFromArray(['id' => 1, 'firstname' => 'Robert', 'lastname' => 'RENAUD',...]);
     * ```
     *
     * @param array $array
     * @return UserInterface
     */
    public static function createFromArray(array $array = []): UserInterface;

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @param int $id
     * @return UserInterface
     */
    public function setId(int $id): UserInterface;

    /**
     * @return array
     */
    public function getExternalId(): array;

    /**
     * @param array $externalId
     * @return UserInterface
     */
    public function setExternalId(array $externalId): UserInterface;

    public function getFirstname(): ?string;

    /**
     * @param string $firstname
     * @return UserInterface
     */
    public function setFirstname(string $firstname): UserInterface;

    public function getLastname(): ?string;

    /**
     * @param string $lastname
     * @return UserInterface
     */
    public function setLastname(string $lastname): UserInterface;

    public function getFullName(): ?string;

    public function getMail(): ?string;

    /**
     * @param string $mail
     * @return UserInterface
     */
    public function setMail(string $mail): UserInterface;

    /**
     * @return string
     */
    public function getLogin(): string;

    /**
     * @param string $login
     * @return UserInterface
     */
    public function setLogin(string $login): UserInterface;

    public function getPhone(): ?string;

    /**
     * @param string|null $phone
     * @return UserInterface
     */
    public function setPhone(?string $phone): UserInterface;

    /**
     * @return int|null
     */
    public function getInternalParapheur(): ?int;

    public function getSignatureSubstitutes(): array;

    public function setSignatureSubstitutes(array $signatureSubstitutes): UserInterface;

    public function getMode(): UserMode;

    public function setMode(UserMode $mode): UserInterface;

    public function getLongTimeToken(): array;

    public function setLongTimeToken(array $longTimeToken): UserInterface;

    public function getStatus(): string;

    public function setStatus(string $status): UserInterface;
}
