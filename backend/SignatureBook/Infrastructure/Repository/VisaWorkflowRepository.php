<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Visa Workflow Repository
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\SignatureBook\Infrastructure\Repository;

use Entity\models\ListInstanceModel;
use Exception;
use MaarchCourrier\Core\Domain\DiffusionList\Port\ListInstanceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\DiffusionList\Domain\ListInstance;
use MaarchCourrier\Core\Domain\DiffusionList\Mode;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;
use SrcCore\models\DatabaseModel;

class VisaWorkflowRepository implements VisaWorkflowRepositoryInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * @param MainResourceInterface $mainResource
     *
     * @return bool
     * @throws Exception
     */
    public function isInWorkflow(MainResourceInterface $mainResource): bool
    {
        $inCircuit = ListInstanceModel::get([
            'select'  => [1],
            'where'   => ['res_id = ?', 'difflist_type = ?', 'process_date is null'],
            'data'    => [$mainResource->getResId(), 'VISA_CIRCUIT'],
            'orderBy' => ['listinstance_id'],
            'limit'   => 1
        ]);

        return !empty($inCircuit[0] ?? null);
    }

    /**
     * @param MainResourceInterface $mainResource
     *
     * @return bool
     * @throws Exception
     */
    public function hasWorkflow(MainResourceInterface $mainResource): bool
    {
        $hasCircuit = ListInstanceModel::get([
            'select' => [1],
            'where'  => ['res_id = ?', 'difflist_type = ?'],
            'data'   => [$mainResource->getResId(), 'VISA_CIRCUIT']
        ]);

        return !empty($hasCircuit[0] ?? null);
    }

    /**
     * @param MainResourceInterface $mainResource
     *
     * @return void
     * @throws Exception
     */
    public function restWorkflowByMainResource(MainResourceInterface $mainResource): void
    {
        ListInstanceModel::update([
            'set'   => ['process_date' => null, 'process_comment' => null],
            'where' => ['res_id = ?', 'difflist_type = ?'],
            'data'  => [$mainResource->getResId(), 'VISA_CIRCUIT']
        ]);
    }

    /**
     * @param MainResourceInterface $mainResource
     *
     * @return bool
     * @throws Exception
     */
    public function isWorkflowActiveByMainResource(MainResourceInterface $mainResource): bool
    {
        $listInstances = ListInstanceModel::get([
            'select' => ['COUNT(*)'],
            'where'  => ['res_id = ?', 'item_mode in (?)', 'process_date IS NULL'],
            'data'   => [$mainResource->getResId(), ['visa', 'sign']]
        ]);

        return ((int)$listInstances[0]['count'] > 0);
    }

    /**
     * @param MainResourceInterface $mainResource
     *
     * @return ?UserInterface
     * @throws Exception
     */
    public function getCurrentStepUserByMainResource(MainResourceInterface $mainResource): ?UserInterface
    {
        $currentStep = ListInstanceModel::getCurrentStepByResId(['resId' => $mainResource->getResId()]);

        if (empty($currentStep['item_id'])) {
            return null;
        }

        return $this->userRepository->getUserById($currentStep['item_id']);
    }

    /**
     * @throws Exception
     */
    public function isLastStepWorkflowByMainResource(MainResourceInterface $mainResource): bool
    {
        $listInstances = ListInstanceModel::get([
            'select' => ['COUNT(*)'],
            'where'  => ['res_id = ?', 'item_mode in (?)', 'process_date IS NULL'],
            'data'   => [$mainResource->getResId(), ['visa', 'sign']]
        ]);

        return ((int)$listInstances[0]['count'] == 1);
    }

    /**
     * @throws Exception
     */
    public function getCurrentStepByMainResource(MainResourceInterface $mainResource): ?ListInstanceInterface
    {
        $currentStepVisaWorkflow = ListInstanceModel::get([
            'select'  => ['*'],
            'where'   => ['res_id = ?', 'item_mode in (?)', 'process_date IS NULL'],
            'data'    => [$mainResource->getResId(), ['visa', 'sign']],
            'orderBy' => ['listinstance_id ASC'],
            'limit'   => 1
        ]);

        if (empty($currentStepVisaWorkflow)) {
            return null;
        }
        $currentStepVisaWorkflow = $currentStepVisaWorkflow[0];

        return (new ListInstance())
            ->setListInstanceId($currentStepVisaWorkflow['listinstance_id'])
            ->setItemId($currentStepVisaWorkflow['item_id'])
            ->setItemType($currentStepVisaWorkflow['item_type'])
            ->setItemMode(Mode::from($currentStepVisaWorkflow['item_mode']))
            ->setProcessDate($currentStepVisaWorkflow['process_date'])
            ->setSequence($currentStepVisaWorkflow['sequence'])
            ->setRequestedSignature($currentStepVisaWorkflow['requested_signature'])
            ->setResId($currentStepVisaWorkflow['res_id'])
            ->setSignatory($currentStepVisaWorkflow['signatory']);
    }

    /**
     * @param ListInstanceInterface $listInstance
     * @param array $values
     * @return void
     * @throws Exception
     */
    public function updateListInstance(ListInstanceInterface $listInstance, array $values): void
    {
        ListInstanceModel::update([
            'set'   => $values,
            'where' => ['listinstance_id = ?'],
            'data'  => [$listInstance->getListInstanceId()]
        ]);
    }

    /**
     * @param MainResourceInterface $mainResource
     *
     * @return ListInstanceInterface[]
     * @throws Exception
     */
    public function getActiveVisaWorkflowByMainResource(MainResourceInterface $mainResource): array
    {
        $list = DatabaseModel::select([
            'select'    => ['listinstance.*'],
            'table'     => ['listinstance', 'users'],
            'left_join' => ['listinstance.item_id = users.id'],
            'where'     => ['res_id = ?', 'item_type = ?', 'difflist_type = ?', 'process_date IS NULL'],
            'data'      => [$mainResource->getResId(), 'user_id', 'VISA_CIRCUIT'],
            'order_by'  => ['listinstance_id ASC'],
        ]);

        if (empty($list)) {
            return [];
        }

        $workflow = [];
        foreach ($list as $item) {
            $workflow[] = ListInstance::createFromArray($item);
        }

        return $workflow;
    }

    /**
     * @param MainResourceInterface $mainResource
     *
     * @return ListInstanceInterface[]
     * @throws Exception
     */
    public function getFullVisaCircuit(MainResourceInterface $mainResource): array
    {
        $list = DatabaseModel::select([
            'select'    => ['listinstance.*'],
            'table'     => ['listinstance', 'users'],
            'left_join' => ['listinstance.item_id = users.id'],
            'where'     => ['res_id = ?', 'item_type = ?', 'difflist_type = ?'],
            'data'      => [$mainResource->getResId(), 'user_id', 'VISA_CIRCUIT'],
            'order_by'  => ['listinstance_id ASC'],
        ]);

        if (empty($list)) {
            return [];
        }

        $workflow = [];
        foreach ($list as $item) {
            $workflow[] = ListInstance::createFromArray($item);
        }

        return $workflow;
    }

    /**
     * @param MainResourceInterface $mainResource
     * @return int|null
     * @throws Exception
     */
    public function getLastSignatoryId(MainResourceInterface $mainResource): ?int
    {
        $step = DatabaseModel::select([
            'select'    => ['listinstance.item_id'],
            'table'     => ['listinstance', 'users'],
            'left_join' => ['listinstance.item_id = users.id'],
            'where'     => ['res_id = ?', 'item_type = ?', 'difflist_type = ?', 'item_mode = ?'],
            'data'      => [$mainResource->getResId(), 'user_id', 'VISA_CIRCUIT', 'sign'],
            'order_by'  => ['listinstance_id DESC'],
            'limit'     => 1
        ]);

        if (empty($step)) {
            return null;
        }

        return $step[0]['item_id'];
    }
}
