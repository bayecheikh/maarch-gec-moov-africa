<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief WebhookValidation class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Webhook;

use DateTime;
use Exception;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentRepositoryInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\AttachmentOutOfPerimeterProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\IdParapheurIsMissingProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceIdEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceIdMasterNotCorrespondingProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\RetrieveDocumentUrlEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\SignedResource;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;

class WebhookValidation
{
    /**
     * @param  AttachmentRepositoryInterface  $attachmentRepository
     * @param  MainResourceRepositoryInterface  $mainResourceRepository
     * @param  UserRepositoryInterface  $userRepository
     * @param  CurrentUserInterface  $currentUser
     */
    public function __construct(
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly MainResourceRepositoryInterface $mainResourceRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly CurrentUserInterface $currentUser
    ) {
    }

    /**
     * @param array $body
     * @param array $decodedToken
     * @return SignedResource
     * @throws AttachmentOutOfPerimeterProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceIdEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     * @throws UserDoesNotExistProblem
     * @throws Exception
     */
    public function validateAndCreateResource(array $body, array $decodedToken): SignedResource
    {
        if (empty($body['retrieveDocUri'])) {
            throw new RetrieveDocumentUrlEmptyProblem();
        }

        if (empty($body['token']) || !isset($decodedToken['userSerialId'])) {
            throw new CurrentTokenIsNotFoundProblem();
        }

        $currentUser = $this->userRepository->getUserById($decodedToken['userSerialId']);
        if ($currentUser === null) {
            throw new UserDoesNotExistProblem();
        }

        $this->currentUser->setCurrentUser($decodedToken['userSerialId']);

        if (!isset($decodedToken['resId'])) {
            throw new ResourceIdEmptyProblem();
        }

        $signedResource = new SignedResource();

        if (empty($body['payload']['idParapheur'])) {
            throw new IdParapheurIsMissingProblem();
        }

        $signedResource->setId($body['payload']['idParapheur']);
        $signedResource->setStatus($body['signatureState']['state']);

        if (!empty($body['signatureState']['message'])) {
            $signedResource->setMessageStatus(
                $body['signatureState']['message']
            );
        }

        if (!empty($body['signatureState']['error'])) {
            $signedResource->setMessageStatus(
                $body['signatureState']['error']
            );
        }

        if ($body['signatureState']['updatedDate'] !== null) {
            $signedResource->setSignatureDate(new DateTime($body['signatureState']['updatedDate']));
        }

        $externalState = [];
        if (array_key_exists('hasDigitalSignature', $body['signatureState'])) {
            $externalState['hasDigitalSignature'] = ($body['signatureState']['hasDigitalSignature'] === 'true');
        }

        if (array_key_exists('hasStampSignature', $body['signatureState'])) {
            $externalState['hasStampSignature'] = ($body['signatureState']['hasStampSignature'] === 'true');
        }

        $signedResource->setExternalState($externalState);

        if (isset($decodedToken['resIdMaster'])) {
            $attachment = $this->attachmentRepository->getAttachmentByResId($decodedToken['resId']);
            if (empty($attachment)) {
                throw new AttachmentOutOfPerimeterProblem();
            }

            $mainResource = $this->mainResourceRepository->getMainResourceByResId($decodedToken['resIdMaster']);
            if (
                !$this->attachmentRepository->checkConcordanceResIdAndResIdMaster($attachment, $mainResource)
            ) {
                throw new ResourceIdMasterNotCorrespondingProblem(
                    $decodedToken['resId'],
                    $decodedToken['resIdMaster']
                );
            }

            $signedResource->setResIdMaster($decodedToken['resIdMaster']);
        }

        $signedResource->setResIdSigned($decodedToken['resId']);

        return $signedResource;
    }
}
