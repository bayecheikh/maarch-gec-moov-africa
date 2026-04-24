<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Workflow Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port;

interface GoodflagWorkflowInterface
{
    public function getId(): string;

    public function setId(string $id): self;

    public function getUserId(): string;

    public function setUserId(string $userId): self;

    public function getName(): string;

    public function setName(string $name): self;

    public function getSteps(): array;

    public function addStep(GoodflagWorkflowItemInterface $step, int $numStep): self;
}
