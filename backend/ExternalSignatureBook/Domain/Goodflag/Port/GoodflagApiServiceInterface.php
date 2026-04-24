<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Api Service Interface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port;

use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\CommonExternalSignatureBookApiServiceInterface;

interface GoodflagApiServiceInterface extends CommonExternalSignatureBookApiServiceInterface
{
    public function getConfig(): GoodflagConfigInterface;

    public function retrieveSignatureProfiles(): array;

    public function retrieveConsentPages(): array;

    public function retrieveUsers(?string $search): array;

    public function retrieveContacts(?string $search): array;

    public function retrieveSpecificContact(string $contactId): array;

    public function retrieveSpecificUser(string $userId): array;

    public function createNewUser(UserInterface $user): string;

    public function setCurrentUserInformations(): void;

    public function createWorkflow(GoodflagWorkflowInterface $workflow): array;

    public function updateWorkflowOwner(GoodflagWorkflowInterface $workflow): array;

    public function startWorkflow(GoodflagWorkflowInterface $workflow): array;

    public function archiveWorkflow(GoodflagWorkflowInterface $workflow): array;

    public function retrieveWorkflow(string $workflowId): array;

    public function createDocumentBlob(
        GoodflagWorkflowInterface $workflow,
        string $documentPath
    ): array;

    public function createDocumentPart(
        GoodflagWorkflowInterface $workflow,
        GoodflagInstanceConfigInterface $templateGoodflag,
        string $blobId,
        string $documentFilename,
        bool $isAnnexDocument
    ): array;

    public function addSignaturePositionsToDocumentPart(
        string $documentId,
        array $signaturePositions
    ): bool;

    public function createWebhook(
        string $endPoint,
        array $notifiedEvents
    ): array;

    public function isWebhookExists(string $search): bool;

    public function retrieveWebhookEvent(
        string $webhookEventId
    ): array;

    public function retrieveDocument(string $documentId): array;

    public function downloadDocuments(GoodflagWorkflowInterface $workflow): string;

    public function downloadDocumentPart(string $documentId, string $hashPart): string;

    public function downloadEvidenceCertificate(GoodflagWorkflowInterface $workflow): string;
}
