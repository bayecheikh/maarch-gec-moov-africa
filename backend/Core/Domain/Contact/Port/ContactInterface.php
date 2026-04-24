<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Contact\Port;

use DateTimeImmutable;

interface ContactInterface
{
    public static function createFromArray(array $data): self;

    // Getters
    public function getId(): int;

    public function getCivility(): ?int;

    public function getFirstname(): ?string;

    public function getLastname(): ?string;

    public function getCompany(): ?string;

    public function getDepartment(): ?string;

    public function getFunction(): ?string;

    public function getAddressNumber(): ?string;

    public function getAddressStreet(): ?string;

    public function getAddressAdditional1(): ?string;

    public function getAddressAdditional2(): ?string;

    public function getAddressPostcode(): ?string;

    public function getAddressTown(): ?string;

    public function getAddressCountry(): ?string;

    public function getEmail(): ?string;

    public function getPhone(): ?string;

    public function getCommunicationMeans(): ?array;

    public function getNotes(): ?string;

    public function getCreator(): int;

    public function getCreationDate(): DateTimeImmutable;

    public function getModificationDate(): ?DateTimeImmutable;

    public function isEnabled(): bool;

    public function getCustomFields(): ?array;

    public function getExternalId(): ?array;

    public function getSector(): ?string;

    public function isLadIndexation(): bool;

    public function isConfidential(): bool;

    //Setters
    public function setId(int $id): self;

    public function setCivility(?int $civility): self;

    public function setFirstname(?string $firstname): self;

    public function setLastname(?string $lastname): self;

    public function setCompany(?string $company): self;

    public function setDepartment(?string $department): self;

    public function setFunction(?string $function): self;

    public function setAddressNumber(?string $addressNumber): self;

    public function setAddressStreet(?string $addressStreet): self;

    public function setAddressAdditional1(?string $addressAdditional1): self;

    public function setAddressAdditional2(?string $addressAdditional2): self;

    public function setAddressPostcode(?string $addressPostcode): self;

    public function setAddressTown(?string $addressTown): self;

    public function setAddressCountry(?string $addressCountry): self;

    public function setEmail(?string $email): self;

    public function setPhone(?string $phone): self;

    public function setCommunicationMeans(?array $communicationMeans): self;

    public function setNotes(?string $notes): self;

    public function setCreator(int $creator): self;

    public function setCreationDate(DateTimeImmutable $creationDate): self;

    public function setModificationDate(?DateTimeImmutable $modificationDate): self;

    public function setEnabled(bool $enabled): self;

    public function setCustomFields(?array $customFields): self;

    public function setExternalId(?array $externalId): self;

    public function setSector(?string $sector): self;

    public function setLadIndexation(bool $ladIndexation): self;

    public function setIsConfidential(bool $isConfidential): self;
}
