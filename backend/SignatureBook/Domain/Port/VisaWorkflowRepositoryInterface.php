<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Visa Workflow Repository Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\Core\Domain\DiffusionList\Port\ListInstanceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface VisaWorkflowRepositoryInterface
{
    public function isWorkflowActiveByMainResource(MainResourceInterface $mainResource): bool;

    public function getCurrentStepUserByMainResource(MainResourceInterface $mainResource): ?UserInterface;

    public function isLastStepWorkflowByMainResource(MainResourceInterface $mainResource): bool;

    public function getCurrentStepByMainResource(MainResourceInterface $mainResource): ?ListInstanceInterface;

    public function updateListInstance(ListInstanceInterface $listInstance, array $values): void;

    /**
     * @param MainResourceInterface $mainResource
     *
     * @return ListInstanceInterface[]
     */
    public function getActiveVisaWorkflowByMainResource(MainResourceInterface $mainResource): array;

    public function isInWorkflow(MainResourceInterface $mainResource): bool;

    public function hasWorkflow(MainResourceInterface $mainResource): bool;

    public function restWorkflowByMainResource(MainResourceInterface $mainResource): void;

    public function getFullVisaCircuit(MainResourceInterface $mainResource): array;

    public function getLastSignatoryId(MainResourceInterface $mainResource): ?int;
}
