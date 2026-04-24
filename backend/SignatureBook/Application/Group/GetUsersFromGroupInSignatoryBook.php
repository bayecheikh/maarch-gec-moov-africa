<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Get Users From Group In Signatory Book
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Group;

use Exception;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookGroupServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\GroupExternalIdNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserInfoRetrievalFailedProblem;

class GetUsersFromGroupInSignatoryBook
{
    /**
     * @param SignatureBookGroupServiceInterface $signatureBookGroupService
     * @param SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader
     */
    public function __construct(
        private readonly SignatureBookGroupServiceInterface $signatureBookGroupService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader,
    ) {
    }

    /**
     * @param GroupInterface $group
     * @return array|false
     * @throws SignatureBookNoConfigFoundProblem
     * @throws GroupExternalIdNotFoundProblem
     * @throws Exception
     */
    public function execute(GroupInterface $group): array|false
    {
        $signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }

        $groupExternalId = $group->getExternalId();
        if (empty($groupExternalId['internalParapheur'])) {
            throw new GroupExternalIdNotFoundProblem();
        }

        $this->signatureBookGroupService->setConfig($signatureBook);

        $users = $this->signatureBookGroupService->getUsersFromGroup($group);
        if ($users === false) {
            throw new UserInfoRetrievalFailedProblem();
        }

        return $users;
    }
}
