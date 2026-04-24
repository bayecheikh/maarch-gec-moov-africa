<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Send Document To Sign class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use Exception;
use MaarchCourrier\Core\Domain\Attachment\AttachmentNotFoundProblem;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentFileRetrieverFactoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\RetrieveMainResourceFileFactoryInterface;
use MaarchCourrier\Core\Domain\Problem\ParameterMustBeGreaterThanZeroException;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\DocumentStorage\Domain\Problem\DocServerDoesNotExistProblem;
use MaarchCourrier\DocumentStorage\Domain\Problem\DocumentFingerprintDoesNotMatchInDocServerProblem;
use MaarchCourrier\DocumentStorage\Domain\Problem\FileNotFoundInDocServerProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagWorkflow;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagWorkflowItem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagWorkflowDatabaseServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagTemplateIdNotFound;
use MaarchCourrier\MainResource\Domain\Problem\MainResourceDoesNotExistProblem;
use Resource\Domain\Exceptions\ConvertedResultException;
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceFailedToGetDocumentFromDocserverException;
use Resource\Domain\Exceptions\ResourceFingerPrintDoesNotMatchException;
use Resource\Domain\Exceptions\ResourceHasNoFileException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;

class GoodflagSendDocumentToSign
{
    public function __construct(
        private readonly GoodflagRetrieveConfiguration $goodflagConfiguration,
        private readonly GoodflagApiServiceInterface $goodflagApiService,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly RetrieveMainResourceFileFactoryInterface $retrieveMainResourceFileFactory,
        private readonly AttachmentFileRetrieverFactoryInterface $attachmentFileRetrieverFactory,
        private readonly GoodflagWorkflowDatabaseServiceInterface $workflowDatabaseService,
        private readonly CurrentUserInterface $currentUser
    ) {
    }


    /**
     * @param int $resId
     * @param string $templateGoodflagId
     * @param array $steps
     * @return array[]
     * @throws AttachmentNotFoundProblem
     * @throws ConvertedResultException
     * @throws DocServerDoesNotExistProblem
     * @throws DocumentFingerprintDoesNotMatchInDocServerProblem
     * @throws FileNotFoundInDocServerProblem
     * @throws GoodflagConfigNotFoundProblem
     * @throws GoodflagTemplateIdNotFound
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     * @throws Exception
     */
    public function execute(
        int $resId,
        string $templateGoodflagId,
        array $steps
    ): array {
        $sentResource = [
            'resource'    => null,
            'attachments' => []
        ];

        // Step 1 : Récupération de la configuration de Goodflag
        $this->goodflagApiService->loadConfig();
        $templateGoodflag = $this->goodflagConfiguration->retrieveTemplateConfiguration($templateGoodflagId);

        $mainResource = $this->mainResourceRepository->getMainResourceByResId($resId);
        if (!$mainResource) {
            throw new MainResourceDoesNotExistProblem();
        }

        // Step 2 Extraire le workflow et les documents à signer depuis steps
        $workflowAndDocsToSign = $this->prepareWorkflowAndDocsToSign($steps);

        // Step 3 : Création du workflow
        $workflow = (new GoodflagWorkflow())
            ->setName($this->generateWorkflowName($mainResource->getSubject()));

        foreach ($workflowAndDocsToSign['steps'] as $numStep => $step) {
            foreach ($step as $itemStep) {
                if (!empty($itemStep['userId'])) {
                    $currentStep = (new GoodflagWorkflowItem())
                        ->setId($itemStep['userId'])
                        ->setRequired($itemStep['isSignatureRequired'] ?? false);
                } else {
                    $currentStep = (new GoodflagWorkflowItem())
                        ->setId(null)
                        ->setEmail($itemStep['email'])
                        ->setPhone($itemStep['phoneNumber'] ?? null)
                        ->setlastname($itemStep['lastname'])
                        ->setfirstname($itemStep['firstname'])
                        ->setCountry($itemStep['country'] ?? null)
                        ->setRequired($itemStep['isSignatureRequired'] ?? false);
                }
                $currentStep->setConsentPageId($templateGoodflag->getConsentPageId());
                $workflow->addStep($currentStep, $numStep);
            }
        }

        $goodflagWorkflow = $this->goodflagApiService->createWorkflow($workflow);
        $workflow->setId($goodflagWorkflow['id']);

        // Step 4.1 : Récupération des utilisateurs Goodflag
        $currentUser = $this->currentUser->getCurrentUser();
        $users = $this->goodflagApiService->retrieveUsers($currentUser->getMail());
        $userGoodflagId = null;
        foreach ($users as $userGoodflag) {
            if ($userGoodflag['email'] === $currentUser->getMail()) {
                $userGoodflagId = $userGoodflag['id'];
                break;
            }
        }
        // Si l'utilisateur Goodflag n'existe pas, on le crée
        if (!$userGoodflagId) {
            $userGoodflagId = $this->goodflagApiService->createNewUser($currentUser);
        }

        // Step 4.2 : Modification du workflow pour modifier le propriétaire du circuit
        $workflow->setUserId($userGoodflagId);
        $this->goodflagApiService->updateWorkflowOwner($workflow);

        // Step 5 : Ajout du/des documents
        $isMainResourceAttached = false;

        // 5.1 : Pour chaque document à signer, l'ajouter au circuit
        foreach ($workflowAndDocsToSign['resourcesToSign'] as $keyDoc => $document) {
            $splitKeyDoc = explode('_', $keyDoc);
            if ($splitKeyDoc[0] === 'mainDocument') {
                $isMainResourceAttached = true;
                $path = $document->getPathInfo()['dirname'] . DIRECTORY_SEPARATOR .
                    $document->getPathInfo()['basename'];
                $filename = $document->getPathInfo()['basename'];
            } else {
                $path = $document['path'];
                $filename = $document['filename'];
            }

            $blob = $this->goodflagApiService->createDocumentBlob($workflow, $path);
            $part = $this->goodflagApiService->createDocumentPart(
                $workflow,
                $templateGoodflag,
                $blob['id'],
                $filename,
                false
            );

            $this->goodflagApiService->addSignaturePositionsToDocumentPart(
                $part['documents'][0]['id'],
                $workflowAndDocsToSign['signaturesPositions'][$keyDoc]
            );

            if ($splitKeyDoc[0] === 'mainDocument') {
                $sentResource['resource'][$resId] = $part['documents'][0]['id'];
            } else {
                $sentResource['attachments'][$splitKeyDoc[1]] = $part['documents'][0]['id'];
            }
        }

        // 5.2 : Récupération du document principal s'il n'est pas dans les pièces à signer
        // et l'ajouter en tant qu'annexe au circuit
        if (!$isMainResourceAttached) {
            $mainDocument = $this->retrieveMainResourceFileFactory::create()->getResourceFile($resId, false);
            $path = $mainDocument->getPathInfo()['dirname'] . DIRECTORY_SEPARATOR .
                $mainDocument->getPathInfo()['basename'];
            //Lors de l'envoi de la PJ, mettre un nom de fichier correspondant à l'objet plutôt que le nom du fichier
            $filename = $mainDocument->getPathInfo()['basename'];

            $blob = $this->goodflagApiService->createDocumentBlob($workflow, $path);
            $this->goodflagApiService->createDocumentPart(
                $workflow,
                $templateGoodflag,
                $blob['id'],
                $filename,
                true
            );
        }

        // Step 6: Démarrer le workflow
        $this->goodflagApiService->startWorkflow($workflow);

        // Step 7: Enregistrer les informations du workflow en BDD
        $this->saveWorkflowToDatabase($resId, $workflow, $workflowAndDocsToSign['steps'], $sentResource);

        return [
            'sended' => [
                'letterbox_coll'   => $sentResource['resource'],
                'attachments_coll' => $sentResource['attachments']
            ]
        ];
    }

    /**
     * Generates a unique workflow name by combining a timestamp and a random string.
     *
     * @param string $subject
     * @return string Returns the generated workflow name.
     */
    private function generateWorkflowName(string $subject): string
    {
        $timestamp = time();
        return "{$subject}_{$timestamp}";
    }

    /**
     * @param array $bodySteps
     * @return array|array[]
     * @throws AttachmentNotFoundProblem
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws DocServerDoesNotExistProblem
     * @throws DocumentFingerprintDoesNotMatchInDocServerProblem
     * @throws FileNotFoundInDocServerProblem
     * @throws ConvertedResultException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    private function prepareWorkflowAndDocsToSign(array $bodySteps): array
    {
        $result = [
            'steps'               => [],
            'resourcesToSign'     => [],
            'signaturesPositions' => [],
        ];

        // Tableau pour éviter les doublons de signataires par étape
        $processedSignatories = [];

        foreach ($bodySteps as $instanceStep) {
            if (!array_key_exists($instanceStep['step'], $result['steps'])) {
                $result['steps'][$instanceStep['step']] = [];
                $processedSignatories[$instanceStep['step']] = [];
            }

            $keyDoc = ($instanceStep['mainDocument']) ? 'mainDocument_' : 'attachment_';
            $keyDoc .= $instanceStep['resId'];

            if (!array_key_exists($keyDoc, $result['resourcesToSign'])) {
                if ($instanceStep['mainDocument']) {
                    $document = $this->retrieveMainResourceFileFactory::create()->getResourceFile(
                        $instanceStep['resId'],
                        false
                    );
                } else {
                    $document = $this->attachmentFileRetrieverFactory::create()->getById($instanceStep['resId'], false);
                }
                $result['resourcesToSign'][$keyDoc] = $document;
                $result['signaturesPositions'][$keyDoc] = [];
            }

            // Créer un identifiant unique pour le signataire selon son type
            $signatoryKey = '';
            $signatoryData = [];

            if (!empty($instanceStep['correspondentId'])) {
                $signatoryKey = $instanceStep['correspondentId'];

                if (str_starts_with($instanceStep['correspondentId'], 'usr_')) {
                    $signatoryData = [
                        'userId'              => $instanceStep['correspondentId'],
                        'isSignatureRequired' => $instanceStep['isSignatureRequired']
                    ];
                } else {
                    $contactsInfos = $this->goodflagApiService->retrieveSpecificContact(
                        $instanceStep['correspondentId']
                    );

                    $signatoryData = [
                        'email'               => $contactsInfos['email'],
                        'firstname'           => $contactsInfos['firstName'],
                        'lastname'            => $contactsInfos['lastName'],
                        'phoneNumber'         => $contactsInfos['phoneNumber'],
                        'country'             => $contactsInfos['country'],
                        'isSignatureRequired' => $instanceStep['isSignatureRequired']
                    ];
                }
            } elseif (!empty($instanceStep['externalInformations'])) {
                // Pour les signataires externes, utiliser l'email comme clé unique
                $signatoryKey = $instanceStep['externalInformations']['email'];
                $signatoryData = [
                    'email'               => $instanceStep['externalInformations']['email'],
                    'firstname'           => $instanceStep['externalInformations']['firstname'],
                    'lastname'            => $instanceStep['externalInformations']['lastname'],
                    'phoneNumber'         => $instanceStep['externalInformations']['phone'],
                    'isSignatureRequired' => $instanceStep['isSignatureRequired']
                ];
            }

            // Ajouter le signataire uniquement s'il n'existe pas déjà pour cette étape
            if (!empty($signatoryKey) && !in_array($signatoryKey, $processedSignatories[$instanceStep['step']])) {
                $result['steps'][$instanceStep['step']][] = $signatoryData;
                $processedSignatories[$instanceStep['step']][] = $signatoryKey;
            }

            if (!empty($instanceStep['signaturePositions'])) {
                $result['signaturesPositions'][$keyDoc][] = [
                    "imagePage"   => $instanceStep['signaturePositions'][0]['page'],
                    "imageX"      => $instanceStep['signaturePositions'][0]['positionX'] * 5.9,
                    "imageY"      => $instanceStep['signaturePositions'][0]['positionY'] * 8.4,
                    "imageWidth"  => 150,
                    "imageHeight" => 90
                ];
            }
        }

        ksort($result['steps']);
        return $result;
    }


    /**
     * @throws Exception
     */
    private function saveWorkflowToDatabase(
        int $resId,
        GoodflagWorkflow $workflow,
        array $steps,
        array $sentResource
    ): void {
        $workflowData = [];

        foreach ($steps as $numStep => $step) {
            foreach ($step as $itemStep) {
                $workflowItem = [
                    'step' => $numStep,
                ];

                if (!empty($itemStep['userId'])) {
                    $workflowItem['id'] = $itemStep['userId'];
                    $workflowItem['mode'] = 'sign';
                    $workflowItem['type'] = 'goodflagUser';
                } else {
                    $workflowItem['mode'] = 'sign';
                    $workflowItem['type'] = 'externalSignatory';
                    $workflowItem['email'] = $itemStep['email'];
                    if (!empty($itemStep['phoneNumber'])) {
                        $workflowItem['phone'] = $itemStep['phoneNumber'];
                    }
                    $workflowItem['lastname'] = $itemStep['lastname'];
                    $workflowItem['firstname'] = $itemStep['firstname'];
                    if (!empty($itemStep['country'])) {
                        $workflowItem['country'] = $itemStep['country'];
                    }
                }
                $workflowItem['required'] = $itemStep['isSignatureRequired'] ?? false;
                $workflowData[] = $workflowItem;
            }
        }

        // Enregistrer pour le document principal s'il a été envoyé
        if (!empty($sentResource['resource'])) {
            $this->workflowDatabaseService->saveWorkflowInfosToDatabase(
                $resId,
                'letterbox_coll',
                $workflow->getId(),
                $workflowData
            );
        }

        // Enregistrer pour les pièces jointes
        foreach ($sentResource['attachments'] as $attachmentId => $goodflagDocumentId) {
            $this->workflowDatabaseService->saveWorkflowInfosToDatabase(
                (int)$attachmentId,
                'attachments_coll',
                $workflow->getId(),
                $workflowData
            );
        }
    }
}
