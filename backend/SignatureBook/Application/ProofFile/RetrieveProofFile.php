<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Proof File class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\ProofFile;

use Exception;
use MaarchCourrier\Authorization\Domain\Problem\PrivilegeForbiddenProblem;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\ProofFileConstructorServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\ResourceToSignRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookProofServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Privilege\DownloadProofFilePrivilege;
use MaarchCourrier\SignatureBook\Domain\Problem\DocumentIsNotSignedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ExternalIdNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;

class RetrieveProofFile
{
    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly PrivilegeCheckerInterface $privilegeChecker,
        private readonly SignatureBookProofServiceInterface $proofService,
        private readonly ResourceToSignRepositoryInterface $resourceToSignRepository,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader,
        private readonly VisaWorkflowRepositoryInterface $visaWorkflowRepository,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ProofFileConstructorServiceInterface $proofFileConstructorService
    ) {
    }

    /**
     * @param int $resId
     * @param bool $isAttachment
     * @return array
     * @throws DocumentIsNotSignedProblem
     * @throws ExternalIdNotFoundProblem
     * @throws ResourceDoesNotExistProblem
     * @throws SignatureBookNoConfigFoundProblem
     * @throws Exception
     */
    public function execute(int $resId, bool $isAttachment): array
    {
        $currentUser = $this->currentUser->getCurrentUser();
        $canDownloadProofFile = $this->privilegeChecker->hasPrivilege($currentUser, new DownloadProofFilePrivilege());

        if (!$canDownloadProofFile) {
            throw new PrivilegeForbiddenProblem((new DownloadProofFilePrivilege())->getName());
        }
        $infosDoc = ($isAttachment) ? $this->resourceToSignRepository->getAttachmentInformations($resId)
            : $this->resourceToSignRepository->getResourceInformations($resId);

        if (empty($infosDoc)) {
            throw new ResourceDoesNotExistProblem();
        }

        if ($isAttachment) {
            if (!$this->resourceToSignRepository->isAttachementSigned($resId)) {
                throw new DocumentIsNotSignedProblem();
            }
        } elseif (!$this->resourceToSignRepository->isResourceSigned($resId)) {
            throw new DocumentIsNotSignedProblem();
        }

        if (!$infosDoc['external_id']) {
            throw new ExternalIdNotFoundProblem();
        }

        $infosDoc = json_decode($infosDoc['external_id'], true);

        if (empty($infosDoc['internalParapheur'])) {
            throw new ExternalIdNotFoundProblem();
        }

        $signatureBookConfig = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($signatureBookConfig === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }

        $this->proofService->setConfig($signatureBookConfig);

        $idParapheur = $infosDoc['internalParapheur'];

        $accessToken = $this->currentUser->generateNewToken();
        $jsonContentApi = $this->proofService->retrieveProofFileFromApi($idParapheur, $accessToken, 'json');

        $constructProofFile = new ConstructProofFile(
            $this->visaWorkflowRepository,
            $this->userRepository,
            $this->proofFileConstructorService
        );

        $attachment = null;
        if ($isAttachment) {
            $attachment = $this->attachmentRepository->getAttachmentByResId($resId);
            $mainDocument = $attachment->getMainResource();
        } else {
            $mainDocument = $this->mainResourceRepository->getMainResourceByResId($resId);
        }

        return $constructProofFile->execute($mainDocument, $attachment, $jsonContentApi);
    }
}
