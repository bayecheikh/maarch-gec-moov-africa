<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Update Workflow In Signature Book
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Workflow;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\Core\Domain\User\Problem\UserIsNotSyncInSignatureBookProblem;
use MaarchCourrier\SignatureBook\Domain\Port\Workflow\SignatureBookWorkflowServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Port\Workflow\UpdateWorkflowInSignatureBookInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Workflow\CheckWorkflowExistenceInSignatureBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Workflow\CouldNotUpdateWorkflowInSignatureBookProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;
use MaarchCourrier\Core\Domain\DiffusionList\Mode;
use MaarchCourrier\SignatureBook\Domain\SignatureMode;

class UpdateWorkflowInSignatureBook implements UpdateWorkflowInSignatureBookInterface
{
    /**
     * @param SignatureServiceConfigLoaderInterface $signatureServiceJsonConfigLoader
     * @param SignatureBookWorkflowServiceInterface $signatureBookWorkflowService
     * @param UserRepositoryInterface $userRepository
     * @param MainResourceRepositoryInterface $mainResourceRepository
     * @param AttachmentRepositoryInterface $attachmentRepository
     */
    public function __construct(
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceJsonConfigLoader,
        private readonly SignatureBookWorkflowServiceInterface $signatureBookWorkflowService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly AttachmentRepositoryInterface $attachmentRepository
    ) {
    }

    /**
     * @param array $resourcesWithListInstance
     * @param SignatureBookResource[] $resourcesToUpdate
     *
     * @return void
     * @throws CheckWorkflowExistenceInSignatureBookProblem
     * @throws CouldNotUpdateWorkflowInSignatureBookProblem
     * @throws ResourceDoesNotExistProblem
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserDoesNotExistProblem
     * @throws UserIsNotSyncInSignatureBookProblem
     */
    public function update(array $resourcesWithListInstance, array $resourcesToUpdate = []): void
    {
        $signatureBook = $this->signatureServiceJsonConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->signatureBookWorkflowService->setConfig($signatureBook);

        foreach ($resourcesWithListInstance as $resourceId => $listInstances) {
            $list = [];
            foreach ($listInstances as $instance) {
                $user = $this->userRepository->getUserById($instance['item_id']);
                if ($user === null) {
                    throw new UserDoesNotExistProblem($instance['item_id']);
                }
                if ($user->getInternalParapheur() === null) {
                    throw new UserIsNotSyncInSignatureBookProblem($instance['item_id']);
                }

                $signatureMode = SignatureMode::RGS_KEY->value;
                $userMode = Mode::tryFrom($instance['item_mode']);
                if ($userMode === null) {
                    $userMode = Mode::VISA->value;
                    $signatureMode = SignatureMode::STAMP->value;
                } else {
                    $userMode = $userMode->value;
                }

                if ($userMode === Mode::VISA->value) {
                    $signatureMode = SignatureMode::STAMP->value;
                }

                $list[] = [
                    'userId'             => $user->getInternalParapheur(),
                    'mode'               => $userMode,
                    'signatureMode'      => $signatureMode,
                    'signaturePositions' => []
                ];
            }

            $mainResource = $this->mainResourceRepository->getMainResourceByResId($resourceId);
            if ($mainResource === null) {
                throw new ResourceDoesNotExistProblem();
            }

            if (!empty($resourcesToUpdate)) {
                $resourcesToSign = $resourcesToUpdate;
            } else {
                $mainResourceToSing = [];
                if ($mainResource->getExternalDocumentId() !== null) {
                    $mainResourceToSing = [SignatureBookResource::createFromMainResource($mainResource)];
                }
                $attachments = $this->attachmentRepository->getAttachmentsWithAnInternalParapheur($mainResource);
                $resourcesToSign = array_merge(
                    $mainResourceToSing,
                    SignatureBookResource::createFromAttachments($attachments)
                );
            }

            foreach ($resourcesToSign as $resource) {
                if (!empty($resource->getExternalDocumentId())) {
                    $result = $this->signatureBookWorkflowService->doesWorkflowExists($resource);
                    if (is_array($result) && !empty($result['error'])) {
                        throw new CheckWorkflowExistenceInSignatureBookProblem($result['error']);
                    }

                    $result = $this->signatureBookWorkflowService->updateWorkflow($resource, $list);
                    if (is_array($result) && !empty($result['error'])) {
                        throw new CouldNotUpdateWorkflowInSignatureBookProblem($result['error']);
                    }
                }
            }
        }
    }
}
