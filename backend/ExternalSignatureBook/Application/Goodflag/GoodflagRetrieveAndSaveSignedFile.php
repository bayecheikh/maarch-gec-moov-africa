<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Retrieve and Save Signed File class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\SignatureBook\Port\CreateVersionServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagWorkflowDatabaseServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagWorkflowInterface;

class GoodflagRetrieveAndSaveSignedFile
{
    public function __construct(
        private readonly GoodflagApiServiceInterface $goodflagApiService,
        private readonly GoodflagWorkflowDatabaseServiceInterface $workflowDatabaseService,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly CreateVersionServiceInterface $createVersionService
    ) {
    }

    /**
     * @param GoodflagWorkflowInterface $workflow
     * @return MainResourceInterface
     * @throws ResourceDoesNotExistProblem
     */
    public function execute(GoodflagWorkflowInterface $workflow): MainResourceInterface
    {
        $this->goodflagApiService->loadConfig();

        // Récupération des documents concernés par le workflow
        // Étape 1 : Récupération des pièces signées
        // Étape 2 : Sauvegarde des pièces en tant que nouvelles versions

        $documents = $this->workflowDatabaseService->retrieveResourcesByWorkflowId($workflow->getId());
        $mainResource = null;
        if (isset($documents['resources']) && count($documents['resources']) > 0) {
            foreach ($documents['resources'] as $doc) {
                $mainResource = $this->mainResourceRepository->getMainResourceByResId($doc['res_id']);
                if ($mainResource === null) {
                    throw new ResourceDoesNotExistProblem();
                }

                $documentId = $doc['documentid'];
                $goodflagDocument = $this->goodflagApiService->retrieveDocument($documentId);
                $hashPart = $this->hashToHex($goodflagDocument['parts'][0]['hash']);

                $signedDoc = base64_encode($this->goodflagApiService->downloadDocumentPart($documentId, $hashPart));

                $this->createVersionService->createSignedVersionForResource(
                    $mainResource,
                    ['encodedFile' => $signedDoc, 'format' => 'pdf', 'isSignedFile' => true],
                );
            }
        }

        if (isset($documents['attachments']) && count($documents['attachments']) > 0) {
            foreach ($documents['attachments'] as $doc) {
                $attachment = $this->attachmentRepository->getAttachmentByResId($doc['res_id']);
                if ($attachment === null) {
                    throw new ResourceDoesNotExistProblem();
                }

                $documentId = $doc['documentid'];
                $goodflagDocument = $this->goodflagApiService->retrieveDocument($documentId);
                $hashPart = $this->hashToHex($goodflagDocument['parts'][0]['hash']);

                $signedDoc = base64_encode($this->goodflagApiService->downloadDocumentPart($documentId, $hashPart));

                $this->createVersionService->createVersionForAttachment(
                    $attachment,
                    ['encodedFile' => $signedDoc, 'format' => 'pdf', 'isSignedFile' => true]
                );

                $mainResource = $attachment->getMainResource();
            }
        }

        return $mainResource;
    }

    private function hashToHex(string $hash): string
    {
        return bin2hex(base64_decode($hash, true));
    }
}
