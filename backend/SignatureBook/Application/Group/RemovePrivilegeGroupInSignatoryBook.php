<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Remove privilege group in signatory book
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Group;

use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeInterface;
use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookGroupServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Privilege\DownloadProofFilePrivilege;
use MaarchCourrier\SignatureBook\Domain\Privilege\SignDocumentPrivilege;
use MaarchCourrier\SignatureBook\Domain\Privilege\VisaDocumentPrivilege;
use MaarchCourrier\SignatureBook\Domain\Problem\GetSignatureBookGroupPrivilegesFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\GroupUpdatePrivilegeInSignatureBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;

class RemovePrivilegeGroupInSignatoryBook
{
    public function __construct(
        private readonly SignatureBookGroupServiceInterface $signatureBookGroupService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceJsonConfigLoader,
        private readonly PrivilegeCheckerInterface $privilegeChecker,
    ) {
    }

    /**
     * @param GroupInterface $group
     * @param PrivilegeInterface $privilege
     * @return GroupInterface
     * @throws GetSignatureBookGroupPrivilegesFailedProblem
     * @throws GroupUpdatePrivilegeInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function removePrivilege(GroupInterface $group, PrivilegeInterface $privilege): GroupInterface
    {
        $signatureBook = $this->signatureServiceJsonConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->signatureBookGroupService->setConfig($signatureBook);

        $externalId = $group->getExternalId() ?? null;
        if (!empty($externalId)) {
            $groupPrivileges = $this->signatureBookGroupService->getGroupPrivileges($group);
            if (!empty($groupPrivileges['errors'])) {
                throw new GetSignatureBookGroupPrivilegesFailedProblem($groupPrivileges);
            }
            if (in_array('indexation', $groupPrivileges) || in_array('manage_documents', $groupPrivileges)) {
                $privilegesToRemove = [];
                if ($privilege instanceof SignDocumentPrivilege) {
                    $privilegesToRemove = [
                        'indexation',
                        'manage_documents',
                    ];
                    if ($this->privilegeChecker->hasGroupPrivilege($group, new VisaDocumentPrivilege())) {
                        $privilegesToRemove = array_diff($privilegesToRemove, ['indexation', 'manage_documents']);
                    } elseif ($this->privilegeChecker->hasGroupPrivilege($group, new DownloadProofFilePrivilege())) {
                        $privilegesToRemove = array_diff($privilegesToRemove, ['manage_documents']);
                    }
                } elseif ($privilege instanceof VisaDocumentPrivilege) {
                    $privilegesToRemove = [
                        'indexation',
                        'manage_documents',
                    ];
                    if ($this->privilegeChecker->hasGroupPrivilege($group, new SignDocumentPrivilege())) {
                        $privilegesToRemove = array_diff($privilegesToRemove, ['indexation', 'manage_documents']);
                    } elseif ($this->privilegeChecker->hasGroupPrivilege($group, new DownloadProofFilePrivilege())) {
                        $privilegesToRemove = array_diff($privilegesToRemove, ['manage_documents']);
                    }
                } elseif ($privilege instanceof DownloadProofFilePrivilege) {
                    $privilegesToRemove = [
                        'manage_documents',
                    ];
                    if (
                        $this->privilegeChecker->hasGroupPrivilege($group, new SignDocumentPrivilege()) ||
                        $this->privilegeChecker->hasGroupPrivilege($group, new VisaDocumentPrivilege())
                    ) {
                        $privilegesToRemove = array_diff($privilegesToRemove, ['manage_documents']);
                    }
                }
                foreach ($privilegesToRemove as $privilege) {
                    $isPrivilegeUpdated =
                        $this->signatureBookGroupService->updatePrivilege($group, $privilege, false);
                    if (!empty($isPrivilegeUpdated['errors'])) {
                        throw new GroupUpdatePrivilegeInSignatureBookFailedProblem($isPrivilegeUpdated);
                    }
                }
            }
        }
        return $group;
    }
}
