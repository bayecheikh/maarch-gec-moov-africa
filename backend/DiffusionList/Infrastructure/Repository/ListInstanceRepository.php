<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ListInstanceRepository class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DiffusionList\Infrastructure\Repository;

use DateTime;
use Entity\models\ListInstanceModel;
use Exception;
use MaarchCourrier\Core\Domain\DiffusionList\Mode;
use MaarchCourrier\Core\Domain\Entity\Port\EntityRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\DiffusionList\Domain\ListInstance;
use MaarchCourrier\DiffusionList\Domain\Port\ListInstanceRepositoryInterface;

class ListInstanceRepository implements ListInstanceRepositoryInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EntityRepositoryInterface $entityRepository
    ) {
    }

    /**
     * Create a ListInstance object from an array
     * @throws Exception
     */
    private function createListInstanceFromData(array $data): ListInstance
    {
        $item = $data['item_type'] === 'user_id' ?
            $this->userRepository->getUserById($data['item_id']) :
            $this->entityRepository->getEntityById($data['item_id']);

        $processDate = !empty($data['process_date']) ? new DateTime($data['process_date']) : null;

        return (new ListInstance())
            ->setListInstanceId($data['listinstance_id'])
            ->setItemId($data['item_id'])
            ->setItemType($data['item_type'] ?? null)
            ->setItemMode(isset($data['item_mode']) ? Mode::from($data['item_mode']) : null)
            ->setRequestedSignature($data['requested_signature'] ?? null)
            ->setProcessDate($processDate)
            ->setSequence($data['sequence'] ?? null)
            ->setResId($data['res_id'] ?? null);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getNextInCircuit(int $resId): ListInstance
    {
        $data = ListInstanceModel::get([
            'select'  => ['listinstance_id', 'item_id', 'item_mode'],
            'where'   => ['res_id = ?', 'difflist_type = ?', 'process_date is null'],
            'data'    => [$resId, 'VISA_CIRCUIT'],
            'orderBy' => ['listinstance_id'],
            'limit'   => 1
        ]);
        $listInstance = [];

        foreach ($data as $datum) {
            $listInstance = (new ListInstance())
                ->setListInstanceId($datum['listinstance_id'])
                ->setItemId($datum['item_id'])
                ->setItemMode($datum['item_mode']);
        }

        return $listInstance;
    }

    /**
     * @param array $args
     * @return ListInstance[]
     * @throws Exception
     */
    public function getListInstanceCircuit(array $args): array
    {
        $data = ListInstanceModel::get([
            'select'  => ['requested_signature', 'item_id', 'listinstance_id'],
            'where'   => ['res_id = ?', 'difflist_type = ?', 'process_date is null'],
            'data'    => [$args['resId'], 'VISA_CIRCUIT'],
            'orderBy' => ['listinstance_id']
        ]);

        return array_map(fn(array $item) => $this->createListInstanceFromData($item), $data);
    }

    /**
     * @param array $set
     * @param ListInstance $listInstance
     * @return void
     * @throws Exception
     */
    public function updateListInstance(ListInstance $listInstance, array $set): void
    {
        ListInstanceModel::update([
            'set'   => $set,
            'where' => ['listinstance_id = ?'],
            'data'  => [$listInstance->getListInstanceId()]
        ]);
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public function getListInstanceByResIdAndType(array $args): array
    {
        $data = ListInstanceModel::get([
            'select'  => ['*'],
            'where'   => ['res_id = ?', 'difflist_type = ?'],
            'data'    => [$args['id'], $args['type']],
            'orderBy' => ['listinstance_id']
        ]);

        return array_map(fn(array $item) => $this->createListInstanceFromData($item), $data);
    }

    /**
     * @param int $resId
     * @param string $type
     * @param array $listInstances
     * @return void
     * @throws Exception
     */
    public function replaceListInstanceByResIdAndType(int $resId, string $type, array $listInstances): void
    {
        ListInstanceModel::delete([
            'where' => ['res_id = ?', 'difflist_type = ?'],
            'data'  => [$resId, $type]
        ]);

        foreach ($listInstances as $listInstance) {
            ListInstanceModel::create([
                'res_id'        => $resId,
                'sequence'      => $listInstance->getSequence(),
                'item_id'       => $listInstance->getItemId(),
                'item_type'     => $listInstance->getItemType(),
                'item_mode'     => $listInstance->getItemMode()->value,
                'added_by_user' => $GLOBALS['id'],
                'viewed'        => 0,
                'difflist_type' => $type
            ]);
        }
    }

    /**
     * @throws Exception
     */
    public function getUsersInDestFromDistributionToServices(): array
    {
        $data = ListInstanceModel::get([
            'select' => ['*'],
            'where'  => ['difflist_type = ?', 'item_mode = ?', 'item_type = ?'],
            'data'   => ['entity_id', 'dest', 'user_id'],
        ]);

        return array_map(fn(array $item) => $this->createListInstanceFromData($item), $data);
    }

    /**
     * @throws Exception
     */
    public function getEntitiesInCopyFromDistributionToServices(): array
    {
        $data = ListInstanceModel::get([
            'select' => ['*'],
            'where'  => ['difflist_type = ?', 'item_mode = ?', 'item_type = ?'],
            'data'   => ['entity_id', 'cc', 'entity_id']
        ]);

        return array_map(fn(array $item) => $this->createListInstanceFromData($item), $data);
    }
}
