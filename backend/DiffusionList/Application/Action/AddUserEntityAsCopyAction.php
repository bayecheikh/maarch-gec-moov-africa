<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief AddUserEntityAsCopy action class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DiffusionList\Application\Action;

use MaarchCourrier\Authorization\Domain\Problem\PrivilegeForbiddenProblem;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\DiffusionList\Mode;
use MaarchCourrier\Core\Domain\Entity\Port\EntityRepositoryInterface;
use MaarchCourrier\Core\Domain\Entity\Problem\EntityDoesNotExistProblem;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterArrayCanNotBeEmptyProblem;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\DiffusionList\Domain\ListInstance;
use MaarchCourrier\DiffusionList\Domain\Port\ListInstanceRepositoryInterface;
use MaarchCourrier\DiffusionList\Domain\Privilege\AdminUsersPrivilege;
use MaarchCourrier\DiffusionList\Domain\Privilege\UpdateDiffusionDetailsPrivilege;
use MaarchCourrier\DiffusionList\Domain\Privilege\UpdateDiffusionExceptRecipientDetailsPrivilege;
use MaarchCourrier\DiffusionList\Domain\Privilege\UpdateDiffusionExceptRecipientProcessPrivilege;
use MaarchCourrier\DiffusionList\Domain\Privilege\UpdateDiffusionProcessPrivilege;

class AddUserEntityAsCopyAction
{
    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly PrivilegeCheckerInterface $privilegeChecker,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly ListInstanceRepositoryInterface $listInstanceRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly EntityRepositoryInterface $entityRepository
    ) {
    }

    /**
     * @param  int  $resId
     * @param  array  $data
     * @return bool
     * @throws EntityDoesNotExistProblem
     * @throws ParameterArrayCanNotBeEmptyProblem
     * @throws PrivilegeForbiddenProblem
     * @throws ResourceDoesNotExistProblem
     * @throws UserDoesNotExistProblem
     */
    public function execute(
        int $resId,
        array $data
    ): bool {
        if (empty($data['usersEntitiesAsCopy'])) {
            throw new ParameterArrayCanNotBeEmptyProblem('usersEntitiesAsCopy');
        }

        // Test des droits de modification de la liste de document
        $currentUser = $this->currentUser->getCurrentUser();
        $canAddUserInCopy =
            $this->privilegeChecker->hasPrivilege($currentUser, new AdminUsersPrivilege())
            || $this->privilegeChecker->hasPrivilege($currentUser, new UpdateDiffusionDetailsPrivilege())
            || $this->privilegeChecker->hasPrivilege($currentUser, new UpdateDiffusionExceptRecipientDetailsPrivilege())
            || $this->privilegeChecker->hasPrivilege($currentUser, new UpdateDiffusionExceptRecipientProcessPrivilege())
            || $this->privilegeChecker->hasPrivilege($currentUser, new UpdateDiffusionProcessPrivilege());

        if (!$canAddUserInCopy) {
            throw new PrivilegeForbiddenProblem((new UpdateDiffusionProcessPrivilege())->getName());
        }

        // Test d'existance du document
        $mainResource = $this->mainResourceRepository->getMainResourceByResId($resId);
        if (!$mainResource) {
            throw new ResourceDoesNotExistProblem();
        }

        // Test d'existence des user et entity à ajouter
        foreach ($data['usersEntitiesAsCopy'] as $userEntity) {
            if ($userEntity['type'] === "user") {
                $userToAdd = $this->userRepository->getUserById($userEntity['id']);
                if (!$userToAdd) {
                    throw new UserDoesNotExistProblem();
                }
            } elseif ($userEntity['type'] === "entity") {
                $entityToAdd = $this->entityRepository->getEntityById($userEntity['id']);
                if (!$entityToAdd) {
                    throw new EntityDoesNotExistProblem();
                }
            }
        }

        // Récupération de la liste de diffusion courante
        $listInstance = $this->listInstanceRepository->getListInstanceByResIdAndType([
            'id'   => $resId,
            'type' => 'entity_id'
        ]);

        $newListInstances = [];
        $numSequence = 0;

        // Dans les utilisateurs/entités à ajouter, suppression des éléments déjà présents en copie
        foreach ($listInstance as $instance) {
            $newListInstances[] = $instance;
            if ($instance->getItemMode() === Mode::COPY) {
                $type = $instance->getItemType() == 'user_id' ? 'user' : 'entity';

                $userOrEntityAlreadyIn = ['id' => $instance->getItemId(), 'type' => $type];
                $data['usersEntitiesAsCopy'] = array_filter(
                    $data['usersEntitiesAsCopy'],
                    function ($item) use ($userOrEntityAlreadyIn) {
                        return $item !== $userOrEntityAlreadyIn;
                    }
                );
            }

            $numSequence++;
        }

        // Création de la nouvelle liste de diffusion
        foreach ($data['usersEntitiesAsCopy'] as $userOrEntityToAdd) {
            $type = $userOrEntityToAdd['type'] == 'user' ? 'user_id' : 'entity_id';

            $newListInstances[] = (new ListInstance())
                ->setResId($resId)
                ->setItemId($userOrEntityToAdd['id'])
                ->setItemType($type)
                ->setSequence($numSequence)
                ->setItemMode(Mode::COPY);

            $numSequence++;
        }

        $this->listInstanceRepository->replaceListInstanceByResIdAndType($resId, 'entity_id', $newListInstances);
        return true;
    }
}
