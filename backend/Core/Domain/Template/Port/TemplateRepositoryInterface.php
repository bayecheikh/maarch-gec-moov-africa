<?php

namespace MaarchCourrier\Core\Domain\Template\Port;

interface TemplateRepositoryInterface
{
    public function getById(int $id): ?TemplateInterface;

    public function getByIds(array $ids): array;
}
