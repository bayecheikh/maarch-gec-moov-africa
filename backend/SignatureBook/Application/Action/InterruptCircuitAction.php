<?php

namespace MaarchCourrier\SignatureBook\Application\Action;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\Basket\Problem\BasketNotFoundProblem;
use MaarchCourrier\Core\Domain\Group\Problem\GroupDoesNotExistProblem;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\MainResource\Domain\Problem\MainResourceDoesNotExistProblem;
use MaarchCourrier\SignatureBook\Application\Action\Checker\SignatureBookActionPermissionChecker;
use MaarchCourrier\SignatureBook\Application\Resource\UseCase\CreateAnnotatedVersionAndSynchroInSignatoryBook;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\Action\ConnectedUserCannotPerformActionInBasketProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\NoDocumentsInSignatureBookForThisId;
use MaarchCourrier\SignatureBook\Domain\Problem\Resource\CannotCreateNewResourceVersionProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Resource\CannotUpdateResourceContentInSignatoryBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;

class InterruptCircuitAction
{
    public ?MainResourceInterface $mainResource = null;
    public ?array $attachments = [];
    public ?SignatureBookServiceConfig $signatureBook = null;
    public ?string $accessToken = null;

    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly SignatureServiceInterface $parapheurSignatureService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader,
        private readonly SignatureBookActionPermissionChecker $signatureBookActionPermissionChecker,
        private readonly CreateAnnotatedVersionAndSynchroInSignatoryBook $createAnnotatedVersionAndSynchroInSignBook
    ) {
    }

    /**
     * @param int $resId
     * @param array $note
     * @param UserInterface $basketOwner
     * @param int $basketId
     * @param int $groupId
     * @param array $data
     * @return bool
     * @throws BasketNotFoundProblem
     * @throws ConnectedUserCannotPerformActionInBasketProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws GroupDoesNotExistProblem
     * @throws MainResourceDoesNotExistProblem
     * @throws NoDocumentsInSignatureBookForThisId
     * @throws SignatureBookNoConfigFoundProblem
     * @throws ResourceDoesNotExistProblem
     * @throws CannotCreateNewResourceVersionProblem
     * @throws CannotUpdateResourceContentInSignatoryBookProblem
     */
    public function execute(
        int $resId,
        array $note,
        UserInterface $basketOwner,
        int $basketId,
        int $groupId,
        array $data
    ): bool {
        $this->signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($this->signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->accessToken = $this->currentUser->getCurrentUserToken();
        if (empty($this->accessToken)) {
            throw new CurrentTokenIsNotFoundProblem();
        }

        if (!isset($data[$resId])) {
            throw new NoDocumentsInSignatureBookForThisId();
        }

        $this->mainResource = $this->mainResourceRepository->getMainResourceByResId($resId);
        if (empty($this->mainResource)) {
            throw new MainResourceDoesNotExistProblem();
        }

        $canIPerformAction = $this->signatureBookActionPermissionChecker->check(
            $this->mainResource,
            $this->currentUser->getCurrentUser(),
            $basketOwner,
            $groupId,
            $basketId
        );
        if (!$canIPerformAction) {
            throw new ConnectedUserCannotPerformActionInBasketProblem(
                $this->mainResource,
                $this->currentUser->getCurrentUser(),
                $basketOwner,
                $groupId,
                $basketId
            );
        }

        // Mise à jour du document avec annotations
        foreach ($data[$resId] as $document) {
            if (!empty($document['fileContentWithAnnotations'])  && $document['isAnnotated'] === true) {
                $resultCreateAndSynchro = $this->createAnnotatedVersionAndSynchroInSignBook->execute(
                    $document['resId'],
                    $document['fileContentWithAnnotations'],
                    $document['isAnnotated'],
                    (isset($document['isAttachment']) && $document['isAttachment']),
                    false
                );

                if ($resultCreateAndSynchro['isUpdatedResource']) {
                    $this->mainResource = $this->mainResourceRepository->getMainResourceByResId($resId);
                }
            }
        }

        $this->attachments =
            $this->attachmentRepository->getAttachmentsInSignatureBookByMainResource($this->mainResource);

        $this->refuseInParapheur($note['content'] ?? null);
        $this->unfreezeAttachments();

        return true;
    }

    public function unfreezeAttachments(): void
    {
        foreach ($this->attachments as $attachment) {
            $this->attachmentRepository->updateAttachment(
                $attachment,
                ['status' => 'A_TRA']
            );
        }
    }

    public function refuseInParapheur(?string $note = null): bool
    {
        if (!empty($this->mainResource->getExternalDocumentId())) {
            $this->parapheurSignatureService->setConfig($this->signatureBook)->refuseSignature(
                $this->accessToken,
                $this->mainResource->getExternalDocumentId(),
                $note
            );
        }

        foreach ($this->attachments as $attachment) {
            if (!empty($attachment->getExternalDocumentId())) {
                $this->parapheurSignatureService->setConfig($this->signatureBook)->refuseSignature(
                    $this->accessToken,
                    $attachment->getExternalDocumentId(),
                    $note
                );
            }
        }

        return true;
    }
}
