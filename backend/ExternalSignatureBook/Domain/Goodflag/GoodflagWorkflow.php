<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Workflow class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag;

use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagWorkflowInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagWorkflowItemInterface;

class GoodflagWorkflow implements GoodflagWorkflowInterface
{
    private ?string $id;
    private string $name;
    private string $userId;
    private array $steps = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function addStep(GoodflagWorkflowItemInterface $step, int $numStep): self
    {
        if (!array_key_exists($numStep, $this->steps)) {
            $this->steps[$numStep] = [];
        }
        $this->steps[$numStep][] = $step;
        return $this;
    }
}
