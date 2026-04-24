<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief continueCircuitAction class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Action;

use Exception;
use MaarchCourrier\Attachment\Domain\Attachment;
use MaarchCourrier\Core\Domain\Basket\Problem\BasketNotFoundProblem;
use MaarchCourrier\Core\Domain\Group\Problem\GroupDoesNotExistProblem;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\MainResource\Domain\MainResource;
use MaarchCourrier\MainResource\Domain\Problem\MainResourceDoesNotExistProblem;
use MaarchCourrier\SignatureBook\Application\Action\Checker\SignatureBookActionPermissionChecker;
use MaarchCourrier\SignatureBook\Application\Resource\UseCase\CreateAnnotatedVersionAndSynchroInSignatoryBook;
use MaarchCourrier\Core\Domain\DiffusionList\Mode;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\Workflow\UpdateWorkflowInSignatureBookInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\Action\ConnectedUserCannotPerformActionInBasketProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\DataToBeSentToTheParapheurAreEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\DigitalSignatureIsMandatoryProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\NoDocumentsInSignatureBookForThisId;
use MaarchCourrier\SignatureBook\Domain\Problem\Resource\CannotCreateNewResourceVersionProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Resource\CannotUpdateResourceContentInSignatoryBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureIsMandatoryProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureNotAppliedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\StampSignatureForbiddenProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;
use User\models\UserModel;

class ContinueCircuitAction
{
    /**
     * @param CurrentUserInterface $currentUser
     * @param SignatureServiceInterface $signatureService
     * @param SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader
     * @param MainResourceRepositoryInterface $mainResourceRepository
     * @param VisaWorkflowRepositoryInterface $visaWorkflowRepository
     * @param UpdateWorkflowInSignatureBookInterface $updateWorkflowInSignatureBook
     * @param SignatureBookActionPermissionChecker $signatureBookActionPermissionChecker
     * @param CreateAnnotatedVersionAndSynchroInSignatoryBook $createAnnotatedVersionAndSynchroInSignBook
     */
    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly SignatureServiceInterface $signatureService,
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceConfigLoader,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly VisaWorkflowRepositoryInterface $visaWorkflowRepository,
        private readonly UpdateWorkflowInSignatureBookInterface $updateWorkflowInSignatureBook,
        private readonly SignatureBookActionPermissionChecker $signatureBookActionPermissionChecker,
        private readonly CreateAnnotatedVersionAndSynchroInSignatoryBook $createAnnotatedVersionAndSynchroInSignBook
    ) {
    }

    /**
     * @param Mode $currentRole
     * @param MainResourceInterface $mainResource
     * @param array $data
     * @return void
     * @throws DigitalSignatureIsMandatoryProblem
     * @throws SignatureIsMandatoryProblem
     * @throws StampSignatureForbiddenProblem
     */
    private function validateBeforeExecution(Mode $currentRole, MainResourceInterface $mainResource, array $data): void
    {
        foreach ($data[$mainResource->getResId()] as $document) {
            if ($currentRole == Mode::SIGN) {
                if (empty($document['certificate']) && empty($document['signatures'])) {
                    throw new SignatureIsMandatoryProblem();
                }

                if (empty($document['certificate']) && $mainResource->getHasDigitalSignature()) {
                    throw new DigitalSignatureIsMandatoryProblem();
                }
            } elseif (!empty($document['signatures']) && $mainResource->getHasDigitalSignature()) {
                throw new StampSignatureForbiddenProblem();
            }
        }
    }

    /**
     * @param int $resId
     * @param array $data
     * @param array $note
     * @param UserInterface $basketOwner
     * @param int $basketId
     * @param int $groupId
     * @return bool
     * @throws BasketNotFoundProblem
     * @throws ConnectedUserCannotPerformActionInBasketProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws DataToBeSentToTheParapheurAreEmptyProblem
     * @throws DigitalSignatureIsMandatoryProblem
     * @throws GroupDoesNotExistProblem
     * @throws MainResourceDoesNotExistProblem
     * @throws NoDocumentsInSignatureBookForThisId
     * @throws SignatureBookNoConfigFoundProblem
     * @throws SignatureIsMandatoryProblem
     * @throws SignatureNotAppliedProblem
     * @throws StampSignatureForbiddenProblem
     * @throws ResourceDoesNotExistProblem
     * @throws CannotCreateNewResourceVersionProblem
     * @throws CannotUpdateResourceContentInSignatoryBookProblem
     * @throws Exception
     */
    public function execute(
        int $resId,
        array $data,
        array $note,
        UserInterface $basketOwner,
        int $basketId,
        int $groupId
    ): bool {
        $signatureBook = $this->signatureServiceConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $accessToken = $this->currentUser->getCurrentUserToken();
        if (empty($accessToken)) {
            throw new CurrentTokenIsNotFoundProblem();
        }

        if (!isset($data[$resId])) {
            throw new NoDocumentsInSignatureBookForThisId();
        }

        $mainResource = $this->mainResourceRepository->getMainResourceByResId($resId);
        if (empty($mainResource)) {
            throw new MainResourceDoesNotExistProblem();
        }

        $canIPerformAction = $this->signatureBookActionPermissionChecker->check(
            $mainResource,
            $this->currentUser->getCurrentUser(),
            $basketOwner,
            $groupId,
            $basketId
        );
        if (!$canIPerformAction) {
            throw new ConnectedUserCannotPerformActionInBasketProblem(
                $mainResource,
                $this->currentUser->getCurrentUser(),
                $basketOwner,
                $groupId,
                $basketId
            );
        }

        $currentStepVisaWorkflow = $this->visaWorkflowRepository->getCurrentStepByMainResource($mainResource);
        $currentRole = $currentStepVisaWorkflow->getItemMode();
        $this->validateBeforeExecution($currentRole, $mainResource, $data);

        $isUserSignatory = false;
        foreach ($data[$resId] as $document) {
            $missingData = [];
            $applySuccess = [];

            $newResId = null;
            if (!empty($document['fileContentWithAnnotations']) && $document['isAnnotated'] === true) {
                $resultCreateAndSynchro = $this->createAnnotatedVersionAndSynchroInSignBook->execute(
                    $document['resId'],
                    $document['fileContentWithAnnotations'],
                    $document['isAnnotated'],
                    (isset($document['isAttachment']) && $document['isAttachment'])
                );

                $newResId = $resultCreateAndSynchro['resId'];

                if (
                    $resultCreateAndSynchro['isUpdatedResource'] &&
                    $currentRole !== Mode::VISA &&
                    !empty($data['digitalCertificate'])
                ) {
                    $resultHash = $this->signatureService
                        ->setConfig($signatureBook)
                        ->hashCertificateStep(
                            $document['documentId'],
                            $document['certificate'],
                            $document['signatures'] ?? [],
                            $accessToken,
                            $document['cookieSession'],
                            []
                        );

                    $document['signatureContentLength'] = $resultHash['signatureContentLength'];
                    $document['signatureFieldName'] = $resultHash['signatureFieldName'];
                    $document['tmpUniqueId'] = $resultHash['tmpUniqueId'];
                }
            }

            $mergeData = [
                'date'   => date('c'),
                'user'   => UserModel::getLabelledUserById(['id' => $GLOBALS['id']]),
                'entity' => UserModel::getPrimaryEntityById(['id' => $GLOBALS['id'], 'select' => ['*']])
            ];

            /*if (
                $currentRole !== Mode::VISA &&
                !empty($data['digitalCertificate'])
            ) {
                $resultHash = $this->signatureService
                    ->setConfig($signatureBook)
                    ->hashCertificateStep(
                        $document['documentId'],
                        $document['certificate'],
                        $document['signatures'] ?? [],
                        $accessToken,
                        $document['cookieSession'],
                        $mergeData
                    );

                $document['signatureContentLength'] = $resultHash['signatureContentLength'];
                $document['signatureFieldName'] = $resultHash['signatureFieldName'];
                $document['tmpUniqueId'] = $resultHash['tmpUniqueId'];
            }*/

            if ($currentRole !== Mode::VISA && !empty($data['digitalCertificate'])) {
                $isUserSignatory = true;
                $requiredData = [
                    'resId',
                    'documentId',
                    'hashSignature',
                    'certificate',
                    'signatureContentLength',
                    'signatureFieldName',
                    'cookieSession'
                ];

                foreach ($requiredData as $requiredDatum) {
                    if (empty($document[$requiredDatum])) {
                        $missingData[] = $requiredDatum;
                    }
                }

                if (!empty($missingData)) {
                    throw new DataToBeSentToTheParapheurAreEmptyProblem($missingData);
                }

                $document['documentId'] = intval($document['documentId'] ?? 0);

                $resourceToSign = [
                    'resId' => $newResId ?? $document['resId']
                ];

                if (isset($document['isAttachment']) && $document['isAttachment']) {
                    $resourceToSign['resIdMaster'] = $resId;
                }

                $applySuccess = $this->signatureService
                    ->setConfig($signatureBook)
                    ->applySignature(
                        $document['documentId'],
                        $document['hashSignature'],
                        $document['signatures'] ?? [],
                        $document['certificate'],
                        $document['signatureContentLength'],
                        $document['signatureFieldName'],
                        $document['tmpUniqueId'] ?? null,
                        $accessToken,
                        $document['cookieSession'],
                        $resourceToSign,
                        $mergeData
                    );
            } else {
                $requiredData = [
                    'resId'
                ];

                if (!empty($document['signatures'])) {
                    $requiredData[] = 'documentId';
                }

                foreach ($requiredData as $requiredDatum) {
                    if (empty($document[$requiredDatum])) {
                        $missingData[] = $requiredDatum;
                    }
                }

                if (!empty($missingData)) {
                    throw new DataToBeSentToTheParapheurAreEmptyProblem($missingData);
                }

                $document['documentId'] = intval($document['documentId'] ?? 0);

                $resourceToSign = [
                    'resId' => $newResId ?? $document['resId']
                ];

                if (isset($document['isAttachment']) && $document['isAttachment']) {
                    $resourceToSign['resIdMaster'] = $resId;
                }

                if (!empty($document['signatures'])) {
                    $isUserSignatory = true;
                }

                // Update the current step from signatory to validator
                // If the user is a signatory and have no digital certificate selected
                $mainResource = (new MainResource())
                    ->setResId($resId);
                $currentStep = $this->visaWorkflowRepository->getCurrentStepByMainResource($mainResource);

                if (
                    $currentStep !== null &&
                    $currentStep->getItemType() == 'user_id' &&
                    !$currentStep->isSignatory() &&
                    $currentStep->isRequestedSignature() &&
                    $currentStep->getItemMode() === Mode::SIGN
                ) {
                    // if true: get, change and update the current workflow for all documents
                    $workflowList = $this->visaWorkflowRepository->getActiveVisaWorkflowByMainResource($mainResource);

                    // only change the current step but keep the others
                    $listInstancesForSignatureBook = [];
                    foreach ($workflowList as $key => $workflow) {
                        $listInstancesForSignatureBook[$mainResource->getResId()][$key] = [
                            'item_id'   => $workflow->getItemId(),
                            'item_mode' => $workflow->getItemMode()->value
                        ];
                        if ($key === 0) {
                            $resId = $mainResource->getResId();
                            $listInstancesForSignatureBook[$resId][$key]['item_mode'] = Mode::VISA->value;
                        }
                    }

                    // which signature book resource to apply the update
                    if (empty($document['isAttachment'] ?? false)) {
                        $mainResource->setExternalDocumentId($document['documentId']);
                        $resourceToUpdate = SignatureBookResource::createFromMainResource($mainResource);
                    } else {
                        $resourceToUpdate = SignatureBookResource::createFromAttachment(
                            (new Attachment())
                                ->setResId($document['resId'])
                                ->setMainResource($mainResource)
                                ->setExternalDocumentId($document['documentId'])
                        );
                    }

                    // send the update workflow
                    $this->updateWorkflowInSignatureBook->update($listInstancesForSignatureBook, [$resourceToUpdate]);
                }

                if ($document['documentId'] !== 0) {
                    $applySuccess = $this->signatureService
                        ->setConfig($signatureBook)
                        ->applySignature(
                            $document['documentId'],
                            null,
                            $document['signatures'] ?? [],
                            null,
                            null,
                            null,
                            $document['tmpUniqueId'] ?? null,
                            $accessToken,
                            null,
                            $resourceToSign,
                            $mergeData
                        );
                }
            }

            if (!empty($applySuccess['errors'])) {
                $error = $applySuccess['errors'];
                if (!empty($applySuccess['context'])) {
                    $error .= " (Message = " . $applySuccess['context']['message'] . ")";
                }
                throw new SignatureNotAppliedProblem($error);
            }
        }

        if ($isUserSignatory) {
            $this->visaWorkflowRepository->updateListInstance($currentStepVisaWorkflow, ['signatory' => 'true']);
        }

        return true;
    }
}
