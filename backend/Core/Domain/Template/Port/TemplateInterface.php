<?php

namespace MaarchCourrier\Core\Domain\Template\Port;

interface TemplateInterface
{
    public function getId(): int;
    public function setId(int $id): TemplateInterface;
    public function getLabel(): string;
    public function setLabel(string $label): TemplateInterface;
    public function getType(): string;
    public function setType(string $type): TemplateInterface;
    public function getSignaturePositions(): array;
    public function setSignaturePositions(array $signaturePositions): TemplateInterface;
}
