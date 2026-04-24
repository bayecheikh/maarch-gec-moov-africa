<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief StoreSignedResource class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Webhook;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\ResourceToSignRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\StoreSignedResourceServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\StoreResourceProblem;
use MaarchCourrier\SignatureBook\Domain\SignedResource;

class StoreSignedResource
{
    public function __construct(
        private readonly ResourceToSignRepositoryInterface $resourceToSignRepository,
        private readonly StoreSignedResourceServiceInterface $storeSignedResourceService,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly VisaWorkflowRepositoryInterface $visaWorkflowRepository
    ) {
    }

    /**
     * @throws StoreResourceProblem
     */
    public function store(SignedResource $signedResource): int
    {
        if ($signedResource->getResIdMaster() !== null) { //pour les PJ
            $attachment = $this->attachmentRepository->getAttachmentByResId($signedResource->getResIdSigned());
            $mainResource = $this->mainResourceRepository->getMainResourceByResId($signedResource->getResIdMaster());

            $isLastStepVisa = $this->visaWorkflowRepository->isLastStepWorkflowByMainResource($mainResource);

            if ($isLastStepVisa) {
                $lastSignatoryId = $this->visaWorkflowRepository->getLastSignatoryId($mainResource);
                if (!empty($lastSignatoryId)) {
                    $signedResource->setUserSerialId($lastSignatoryId);
                }
            }
            $id = $this->storeSignedResourceService->storeAttachement($signedResource, $attachment, $isLastStepVisa);

            $externalId = [
                'internalParapheur' => $signedResource->getId()
            ];
            $attachment = $this->attachmentRepository->updateAttachment(
                $attachment,
                ['external_id' => json_encode($externalId)]
            );

            $newAttachment = $this->attachmentRepository->getAttachmentByResId($id);
            $externalState = [
                'hasDigitalSignature' => $signedResource->getHasDigitalSignature(),
                'hasStampSignature'   => $signedResource->getHasStampSignature()
            ];

            $this->attachmentRepository->updateAttachment(
                $newAttachment,
                ['external_state' => json_encode($externalState)]
            );

            if ($isLastStepVisa) {
                $this->attachmentRepository->updateAttachment($attachment, ['status' => 'SIGN']);
            } else {
                $this->attachmentRepository->updateAttachment($attachment, ['status' => 'OBS']);

                $this->attachmentRepository->updateAttachment(
                    $newAttachment,
                    ['relation' => $attachment->getRelation() + 1]
                );

                $externalId = [
                    'internalParapheur' => $attachment->getExternalDocumentId()
                ];
                $this->attachmentRepository->updateAttachment(
                    $newAttachment,
                    ['external_id' => json_encode($externalId)]
                );
            }
        } else { //pour les resources
            $mainResource = $this->mainResourceRepository->getMainResourceByResId($signedResource->getResIdSigned());
            $isLastStepVisa = $this->visaWorkflowRepository->isLastStepWorkflowByMainResource($mainResource);

            $storeResource = $this->storeSignedResourceService->storeResource($signedResource);
            if (!empty($storeResource['errors'])) {
                throw new StoreResourceProblem($storeResource['errors']);
            } else {
                $currentVersion = $mainResource->getVersion();
                $currentExternalState = $mainResource->getExternalState();
                if ($isLastStepVisa) {
                    $this->resourceToSignRepository->createSignVersionForResource(
                        $signedResource->getResIdSigned(),
                        $storeResource
                    );
                } else {
                    $this->resourceToSignRepository->createIntermediateSignedVersionForResource(
                        $signedResource->getResIdSigned(),
                        $storeResource
                    );
                }
                $this->resourceToSignRepository->setResourceExternalId(
                    $signedResource->getResIdSigned(),
                    $signedResource->getId()
                );

                $externalState = [
                    'hasDigitalSignature' => $signedResource->getHasDigitalSignature(),
                    'hasStampSignature'   => $signedResource->getHasStampSignature(),
                    'lastUnsignedVersion' => $currentExternalState['lastUnsignedVersion'] ?? $currentVersion
                ];
                $this->resourceToSignRepository->setResourceInformations(
                    $signedResource->getResIdSigned(),
                    ['external_state' => json_encode($externalState)]
                );

                $id = $signedResource->getResIdSigned();
            }
        }

        return $id;
    }
}
