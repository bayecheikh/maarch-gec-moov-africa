<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Update Resource Content In Signatory Book
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Resource;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\MaarchParapheurResourceServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceConfigLoaderInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\Resource\CannotUpdateResourceContentInSignatoryBookProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;

class UpdateResourceContentInSignatoryBook
{
    public function __construct(
        private readonly SignatureServiceConfigLoaderInterface $signatureServiceJsonConfigLoader,
        private readonly MaarchParapheurResourceServiceInterface $maarchParapheurResourceService,
        private readonly CurrentUserInterface $currentUser
    ) {
    }

    /**
     * @throws SignatureBookNoConfigFoundProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws CannotUpdateResourceContentInSignatoryBookProblem
     */
    public function execute(MainResourceInterface|AttachmentInterface $resource, string $encodedContent): void
    {
        $accessToken = $this->currentUser->getCurrentUserToken();
        if (empty($accessToken)) {
            throw new CurrentTokenIsNotFoundProblem();
        }

        $signatureBook = $this->signatureServiceJsonConfigLoader->getSignatureServiceConfig();
        if ($signatureBook === null) {
            throw new SignatureBookNoConfigFoundProblem();
        }
        $this->maarchParapheurResourceService->setConfig($signatureBook);

        if ($resource instanceof MainResourceInterface) {
            $signatureBookResource = (new SignatureBookResource())::createFromMainResource($resource);
        } else {
            $signatureBookResource = (new SignatureBookResource())::createFromAttachment($resource);
        }

        $result = $this->maarchParapheurResourceService->updateResourceContent(
            $signatureBookResource,
            $encodedContent,
            $accessToken
        );
        if (is_array($result) && !empty($result['error'])) {
            throw new CannotUpdateResourceContentInSignatoryBookProblem($result['error']);
        }
    }
}
