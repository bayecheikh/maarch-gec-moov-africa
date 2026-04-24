<?php

namespace MaarchCourrier\Template\Domain;

use JsonSerializable;
use MaarchCourrier\Core\Domain\Template\Port\TemplateInterface;

class Template implements TemplateInterface, JsonSerializable
{
    private int $id;
    private string $label = '';
    private string $type = '';
    private array $signaturePositions = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): TemplateInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): TemplateInterface
    {
        $this->label = $label;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): TemplateInterface
    {
        $this->type = $type;
        return $this;
    }

    public function getSignaturePositions(): array
    {
        return $this->signaturePositions;
    }

    public function setSignaturePositions(array $signaturePositions): TemplateInterface
    {
        $this->signaturePositions = $signaturePositions;
        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id'                 => $this->id,
            'signaturePositions' => $this->signaturePositions
        ];
    }
}
