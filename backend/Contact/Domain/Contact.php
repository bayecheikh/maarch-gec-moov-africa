<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Contact\Domain;

use DateTimeImmutable;
use Exception;
use JsonSerializable;
use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;

class Contact implements ContactInterface, JsonSerializable
{
    private int $id;
    private ?int $civility = null;
    private ?string $firstname = null;
    private ?string $lastname = null;
    private ?string $company = null;
    private ?string $department = null;
    private ?string $function = null;
    private ?string $addressNumber = null;
    private ?string $addressStreet = null;
    private ?string $addressAdditional1 = null;
    private ?string $addressAdditional2 = null;
    private ?string $addressPostcode = null;
    private ?string $addressTown = null;
    private ?string $addressCountry = null;
    private ?string $email = null;
    private ?string $phone = null;
    private ?array $communicationMeans = null;
    private ?string $notes = null;
    private int $creator;
    private DateTimeImmutable $creationDate;
    private ?DateTimeImmutable $modificationDate = null;
    private bool $enabled = true;
    private ?array $customFields = null;
    private ?array $externalId = null;
    private ?string $sector = null;
    private bool $ladIndexation = false;
    private bool $isConfidential = false;

    /**
     * Creates a Contact object from a data array
     *
     * @param array{
     *     id: int,
     *     civility: ?int,
     *     firstname: ?string,
     *     lastname: ?string,
     *     company: ?string,
     *     department: ?string,
     *     function: ?string,
     *     address_number: ?string,
     *     address_street: ?string,
     *     address_additional1: ?string,
     *     address_additional2: ?string,
     *     address_postcode: ?string,
     *     address_town: ?string,
     *     address_country: ?string,
     *     email: ?string,
     *     phone: ?string,
     *     communication_means: ?array,
     *     notes: ?string,
     *     creator: int,
     *     modification_date: ?string,
     *     sector: ?string,
     *     creation_date?: string,
     *     enabled?: bool,
     *     custom_fields?: ?string,
     *     external_id?: ?string,
     *     lad_indexation?: bool,
     *     is_confidential?: bool
     * } $data
     *     Fields followed by ? are optional
     *      - creation_date: date format compatible with DateTimeImmutable, default: current date
     *      - enabled: indicates if the contact is active, default: true
     *      - custom_fields: JSON encoded, default: []
     *      - external_id: JSON encoded, default: []
     *      - lad_indexation: LAD indexation indicator, default: false.
     *
     * @return Contact
     * @throws Exception
     */
    public static function createFromArray(array $data): self
    {
        return (new Contact())
            ->setId($data['id'])
            ->setCivility($data['civility'])
            ->setFirstname($data['firstname'])
            ->setLastname($data['lastname'])
            ->setCompany($data['company'])
            ->setDepartment($data['department'])
            ->setFunction($data['function'])
            ->setAddressNumber($data['address_number'])
            ->setAddressStreet($data['address_street'])
            ->setAddressAdditional1($data['address_additional1'])
            ->setAddressAdditional2($data['address_additional2'])
            ->setAddressPostcode($data['address_postcode'])
            ->setAddressTown($data['address_town'])
            ->setAddressCountry($data['address_country'])
            ->setEmail($data['email'])
            ->setPhone($data['phone'])
            ->setCommunicationMeans($data['communication_means'])
            ->setNotes($data['notes'])
            ->setCreator($data['creator'])
            ->setCreationDate(
                !empty($data['creation_date'] ?? null) ?
                    new DateTimeImmutable($data['creation_date']) :
                    new DateTimeImmutable()
            )
            ->setModificationDate(new DateTimeImmutable($data['modification_date']))
            ->setEnabled($data['enabled'] ?? true)
            ->setCustomFields(json_decode($data['custom_fields'] ?? '[]', true))
            ->setExternalId(json_decode($data['external_id'] ?? '[]', true))
            ->setSector($data['sector'])
            ->setLadIndexation($data['lad_indexation'] ?? false)
            ->setIsConfidential($data['is_confidential'] ?? false);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCivility(): ?int
    {
        return $this->civility;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function getFunction(): ?string
    {
        return $this->function;
    }

    public function getAddressNumber(): ?string
    {
        return $this->addressNumber;
    }

    public function getAddressStreet(): ?string
    {
        return $this->addressStreet;
    }

    public function getAddressAdditional1(): ?string
    {
        return $this->addressAdditional1;
    }

    public function getAddressAdditional2(): ?string
    {
        return $this->addressAdditional2;
    }

    public function getAddressPostcode(): ?string
    {
        return $this->addressPostcode;
    }

    public function getAddressTown(): ?string
    {
        return $this->addressTown;
    }

    public function getAddressCountry(): ?string
    {
        return $this->addressCountry;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getCommunicationMeans(): ?array
    {
        return $this->communicationMeans;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreator(): int
    {
        return $this->creator;
    }

    public function getCreationDate(): DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?DateTimeImmutable
    {
        return $this->modificationDate;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    public function getExternalId(): ?array
    {
        return $this->externalId;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function isLadIndexation(): bool
    {
        return $this->ladIndexation;
    }

    public function isConfidential(): bool
    {
        return $this->isConfidential;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setCivility(?int $civility): self
    {
        $this->civility = $civility;
        return $this;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;
        return $this;
    }

    public function setFunction(?string $function): self
    {
        $this->function = $function;
        return $this;
    }

    public function setAddressNumber(?string $addressNumber): self
    {
        $this->addressNumber = $addressNumber;
        return $this;
    }

    public function setAddressStreet(?string $addressStreet): self
    {
        $this->addressStreet = $addressStreet;
        return $this;
    }

    public function setAddressAdditional1(?string $addressAdditional1): self
    {
        $this->addressAdditional1 = $addressAdditional1;
        return $this;
    }

    public function setAddressAdditional2(?string $addressAdditional2): self
    {
        $this->addressAdditional2 = $addressAdditional2;
        return $this;
    }

    public function setAddressPostcode(?string $addressPostcode): self
    {
        $this->addressPostcode = $addressPostcode;
        return $this;
    }

    public function setAddressTown(?string $addressTown): self
    {
        $this->addressTown = $addressTown;
        return $this;
    }

    public function setAddressCountry(?string $addressCountry): self
    {
        $this->addressCountry = $addressCountry;
        return $this;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function setCommunicationMeans(?array $communicationMeans): self
    {
        $this->communicationMeans = $communicationMeans;
        return $this;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function setCreator(int $creator): self
    {
        $this->creator = $creator;
        return $this;
    }

    public function setCreationDate(DateTimeImmutable $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function setModificationDate(?DateTimeImmutable $modificationDate): self
    {
        $this->modificationDate = $modificationDate;
        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function setCustomFields(?array $customFields): self
    {
        $this->customFields = $customFields;
        return $this;
    }

    public function setExternalId(?array $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function setSector(?string $sector): self
    {
        $this->sector = $sector;
        return $this;
    }

    public function setLadIndexation(bool $ladIndexation): self
    {
        $this->ladIndexation = $ladIndexation;
        return $this;
    }

    public function setIsConfidential(bool $isConfidential): self
    {
        $this->isConfidential = $isConfidential;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'                    => $this->id,
            'civility'              => $this->civility,
            'firstname'             => $this->firstname,
            'lastname'              => $this->lastname,
            'company'               => $this->company,
            'function'              => $this->function,
            'address_number'        => $this->addressNumber,
            'address_street'        => $this->addressStreet,
            'address_additional1'   => $this->addressAdditional1,
            'address_additional2'   => $this->addressAdditional2,
            'address_postcode'      => $this->addressPostcode,
            'address_town'          => $this->addressTown,
            'address_country'       => $this->addressCountry,
            'email'                 => $this->email,
            'isConfidential'        => $this->isConfidential
        ];
    }
}
