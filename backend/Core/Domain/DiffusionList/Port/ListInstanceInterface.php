<?php

namespace MaarchCourrier\Core\Domain\DiffusionList\Port;

use MaarchCourrier\Core\Domain\DiffusionList\Mode;

interface ListInstanceInterface
{
    public static function createFromArray(array $values): ListInstanceInterface;
    public function getListInstanceId(): int;
    public function setListInstanceId(int $listInstanceId): ListInstanceInterface;
    public function getResId(): int;
    public function setResId(int $resId): ListInstanceInterface;
    public function getSequence(): int;
    public function setSequence(int $sequence): ListInstanceInterface;
    public function getItemId(): int;
    public function setItemId(int $itemId): ListInstanceInterface;
    public function getExternalId(): ?int;
    public function setExternalId(?int $externalId): ListInstanceInterface;
    public function getItemType(): string;
    public function setItemType(string $itemType): ListInstanceInterface;
    public function getItemMode(): Mode;
    public function setItemMode(Mode $itemMode): ListInstanceInterface;
    public function isRequestedSignature(): bool;
    public function setRequestedSignature(bool $requestedSignature): ListInstanceInterface;
    public function getProcessDate(): ?\DateTimeInterface;
    public function setProcessDate(\DateTimeInterface $processDate): ListInstanceInterface;
    public function isSignatory(): bool;
    public function setSignatory(bool $signatory): ListInstanceInterface;
}
