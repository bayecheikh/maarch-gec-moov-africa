<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ListInstance class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DiffusionList\Domain;

use Exception;
use JsonSerializable;
use MaarchCourrier\Core\Domain\DiffusionList\Mode;
use MaarchCourrier\Core\Domain\DiffusionList\Port\ListInstanceInterface;

class ListInstance implements ListInstanceInterface, JsonSerializable
{
    private int $listInstanceId;
    private int $resId;
    private int $sequence;
    private int $itemId;
    private ?int $externalId = null;
    private string $itemType;
    private Mode $itemMode;
    private bool $requestedSignature;
    private bool $signatory;
    private ?\DateTimeInterface $processDate;

    /**
     * @throws Exception
     */
    public static function createFromArray(array $values): ListInstanceInterface
    {
        $processDate = empty($values['process_date'] ?? null) ? null : new \DateTime($values['process_date']);
        return (new ListInstance())
            ->setListInstanceId($values['listinstance_id'])
            ->setResId($values['res_id'])
            ->setSequence($values['sequence'])
            ->setItemId($values['item_id'])
            ->setItemType($values['item_type'])
            ->setItemMode(Mode::from($values['item_mode']))
            ->setRequestedSignature($values['requested_signature'])
            ->setSignatory($values['signatory'])
            ->setProcessDate($processDate);
    }

    public function getListInstanceId(): int
    {
        return $this->listInstanceId;
    }

    public function setListInstanceId(int $listInstanceId): ListInstance
    {
        $this->listInstanceId = $listInstanceId;
        return $this;
    }

    public function getResId(): int
    {
        return $this->resId;
    }

    public function setResId(int $resId): ListInstance
    {
        $this->resId = $resId;
        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): ListInstance
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): ListInstance
    {
        $this->itemId = $itemId;
        return $this;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): ListInstance
    {
        $this->itemType = $itemType;
        return $this;
    }

    public function getItemMode(): Mode
    {
        return $this->itemMode;
    }

    public function setItemMode(Mode $itemMode): ListInstance
    {
        $this->itemMode = $itemMode;
        return $this;
    }

    public function isRequestedSignature(): bool
    {
        return $this->requestedSignature;
    }

    public function setRequestedSignature(bool $requestedSignature): ListInstance
    {
        $this->requestedSignature = $requestedSignature;
        return $this;
    }

    public function getProcessDate(): ?\DateTimeInterface
    {
        return $this->processDate;
    }

    public function setProcessDate(?\DateTimeInterface $processDate): ListInstance
    {
        $this->processDate = $processDate;
        return $this;
    }

    public function setSignatory(bool $signatory): ListInstance
    {
        $this->signatory = $signatory;
        return $this;
    }

    public function isSignatory(): bool
    {
        return $this->signatory;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(?int $externalId): ListInstance
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'listinstance_id'     => $this->listInstanceId,
            'res_id'              => $this->resId,
            'sequence'            => $this->sequence,
            'item_id'             => $this->itemId,
            'item_type'           => $this->itemType,
            'externalId'          => $this->externalId,
            'item_mode'           => $this->itemMode,
            'requested_signature' => $this->requestedSignature,
            'signatory'           => $this->signatory,
            'process_date'        => $this->processDate
        ];
    }
}
