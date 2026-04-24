<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Construct Proof File class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\ProofFile;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Port\ProofFileConstructorServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\VisaWorkflowRepositoryInterface;

class ConstructProofFile
{
    public function __construct(
        private readonly VisaWorkflowRepositoryInterface $visaWorkflowRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ProofFileConstructorServiceInterface $proofFileConstructorService
    ) {
    }

    public function execute(
        MainResourceInterface $mainResource,
        ?AttachmentInterface $attachment,
        array $infosProofFileApi
    ): array {
        $infosDocument = [
            'typist'       => (!empty($attachment)) ? $attachment->getTypist() : $mainResource->getTypist(),
            'creationDate' => (!empty($attachment)) ? $attachment->getCreationDate() : $mainResource->getCreationDate(),
            'chrono'       => (!empty($attachment)) ? $attachment->getChrono() : $mainResource->getChrono(),
            'title'        => (!empty($attachment)) ? $attachment->getTitle() : $mainResource->getSubject(),
            'filename'     => (!empty($attachment)) ? $attachment->getFilename() : $mainResource->getFilename(),
            'fingerprint'  => (!empty($attachment)) ? $attachment->getFingerprint() : $mainResource->getFingerprint(),
            'isAttachment' => (!empty($attachment))
        ];

        $visaWorkflow = $this->visaWorkflowRepository->getFullVisaCircuit($mainResource);

        $documentPathToZip = [];

        $formatedHistory = $this->constructHistory($visaWorkflow, $infosDocument, $infosProofFileApi);
        $filenameXml = $this->proofFileConstructorService->makeXmlFromArray($formatedHistory, 'root');

        $documentPathToZip[] = [
            'path'     => $filenameXml,
            'filename' => (!empty($attachment)) ? 'proofFile_attach_' . $attachment->getResId() . '.xml' :
                'proofFile_mainDoc_' . $mainResource->getResId() . '.xml'
        ];

        $filenamePdf = $this->proofFileConstructorService->makePdfFromArray($formatedHistory);
        $documentPathToZip[] = [
            'path'     => $filenamePdf,
            'filename' => (!empty($attachment)) ? 'proofFile_attach_' . $attachment->getResId() . '.pdf' :
                'proofFile_mainDoc_' . $mainResource->getResId() . '.pdf'
        ];

        $zipFileContent = $this->proofFileConstructorService->createZip($documentPathToZip);

        return ['encodedProofDocument' => base64_encode($zipFileContent['fileContent']), 'format' => 'zip'];
    }

    private function constructHistory(array $visaWorkflow, array $infosDocument, array $infosProofFileApi): array
    {
        $history = [
            'resource' => [
                'type'         => ($infosDocument['isAttachment']) ? 'attachment' : 'mainDocument',
                'creator'      => $infosDocument['typist']->getFirstname() . ' ' .
                    $infosDocument['typist']->getLastname(),
                'creationDate' => $infosDocument['creationDate']->format('d/m/Y h:m:s'),
                'chrono'       => $infosDocument['chrono'],
                'title'        => $infosDocument['title'],
                'filename'     => $infosDocument['filename'],
                'fingerprint'  => $infosDocument['fingerprint'],
                'isAttachment' => $infosDocument['isAttachment']
            ]
        ];

        $history['workflow'] = [];
        foreach ($visaWorkflow as $visaWorkflowItem) {
            if ($visaWorkflowItem->getItemMode()->value === 'sign') {
                break;
            }
            $stepWorkflow = [];
            $itemUser = $this->userRepository->getUserById($visaWorkflowItem->getItemId());

            $stepWorkflow['user'] = [
                'firstname' => $itemUser->getFirstname(),
                'lastname'  => $itemUser->getLastname(),
                'email'     => $itemUser->getMail(),
            ];
            $stepWorkflow['role'] = $visaWorkflowItem->getItemMode()->value;
            $stepWorkflow['processDate'] = $visaWorkflowItem->getProcessDate()->format('d/m/Y h:m:s');

            $history['workflow'][] = $stepWorkflow;
        }

        if (!empty($infosProofFileApi['proofContent'])) {
            foreach ($infosProofFileApi['proofContent'] as $apiHistoryItem) {
                $stepWorkflow = [];
                if ($apiHistoryItem['type'] === 'ACTION') {
                    $stepWorkflow['user'] = [
                        'firstname' => $apiHistoryItem['user']['firstname'],
                        'lastname'  => $apiHistoryItem['user']['lastname'],
                        'email'     => $apiHistoryItem['user']['email'],
                    ];

                    $stepWorkflow['role'] = $apiHistoryItem['data']['mode'];
                    $stepWorkflow['signatureMode'] = $apiHistoryItem['data']['signatureMode'];
                    $stepWorkflow['processDate'] = $apiHistoryItem['date'];
                    $stepWorkflow['message'] = $apiHistoryItem['message'];
                    $stepWorkflow['document'] = $apiHistoryItem['document'] ?? null;

                    $history['workflow'][] = $stepWorkflow;
                }
            }
        }

        return $history;
    }
}
