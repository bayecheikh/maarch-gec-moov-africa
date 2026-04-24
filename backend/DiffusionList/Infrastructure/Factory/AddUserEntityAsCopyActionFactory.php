<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief AddUserToCopyActionFactory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DiffusionList\Infrastructure\Factory;

use MaarchCourrier\Authorization\Infrastructure\PrivilegeChecker;
use MaarchCourrier\DiffusionList\Application\Action\AddUserEntityAsCopyAction;
use MaarchCourrier\DiffusionList\Infrastructure\Repository\ListInstanceRepository;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;

class AddUserEntityAsCopyActionFactory
{
    public static function create(): AddUserEntityAsCopyAction
    {
        $userRepository = new UserRepository();
        $templateRepository = new TemplateRepository();
        $entityRepository = new EntityRepository();
        $mainResourceRepository = new MainResourceRepository(
            $userRepository,
            $templateRepository,
            $entityRepository
        );

        return new AddUserEntityAsCopyAction(
            new CurrentUserInformations(),
            new PrivilegeChecker(),
            $mainResourceRepository,
            new ListInstanceRepository($userRepository, $entityRepository),
            new UserRepository(),
            new EntityRepository()
        );
    }
}
