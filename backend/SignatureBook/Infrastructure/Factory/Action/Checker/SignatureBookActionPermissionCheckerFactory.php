<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Perform Action Checker Factory class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Factory\Action\Checker;

use MaarchCourrier\Basket\Infrastructure\Repository\BasketRepository;
use MaarchCourrier\Basket\Infrastructure\Repository\RedirectBasketRepository;
use MaarchCourrier\Basket\Infrastructure\Service\BasketClauseService;
use MaarchCourrier\Group\Infrastructure\Repository\GroupRepository;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\SignatureBook\Application\Action\Checker\SignatureBookActionPermissionChecker;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\VisaWorkflowRepository;
use MaarchCourrier\Template\Infrastructure\Repository\TemplateRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use MaarchCourrier\Entity\Infrastructure\Repository\EntityRepository;

class SignatureBookActionPermissionCheckerFactory
{
    public function create(): SignatureBookActionPermissionChecker
    {
        return new SignatureBookActionPermissionChecker(
            new BasketRepository(),
            new BasketClauseService(),
            new GroupRepository(),
            new MainResourceRepository(
                new UserRepository(),
                new TemplateRepository(),
                new EntityRepository()
            ),
            new VisaWorkflowRepository(new UserRepository()),
            new RedirectBasketRepository()
        );
    }
}
