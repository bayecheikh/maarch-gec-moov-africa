<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Attachment Repository Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Attachment\Port;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;

interface AttachmentRepositoryInterface
{
    /**
     * @return AttachmentInterface[]
     */
    public function getAttachmentsInSignatureBookByMainResource(MainResourceInterface $mainResource): array;

    /**
     * @return AttachmentInterface[]
     */
    public function getAttachmentsWithAnInternalParapheur(MainResourceInterface $mainResource): array;

    /**
     * @param int $resId
     * @return AttachmentInterface|null
     */
    public function getAttachmentByResId(int $resId): ?AttachmentInterface;

    public function updateAttachment(AttachmentInterface $attachment, array $values): AttachmentInterface;

    /**
     * @param AttachmentInterface $attachment
     * @return bool
     */
    public function isSigned(AttachmentInterface $attachment): bool;

    /**
     * @param AttachmentInterface $attachment
     * @param MainResourceInterface $mainResource
     * @return bool
     */
    public function checkConcordanceResIdAndResIdMaster(
        AttachmentInterface $attachment,
        MainResourceInterface $mainResource
    ): bool;

    /**
     * @param AttachmentInterface $attachment
     *
     * @return void
     */
    public function removeSignatureBookLink(AttachmentInterface $attachment): void;

    public function getLastNotAnnotatedAttachmentByResId(int $resId): ?AttachmentInterface;

    /**
     * Get the sign attachment from the table res_attachments
     * @param int $originId
     * @return AttachmentInterface|null
     */
    public function getSignAttachmentByOriginId(int $originId): ?AttachmentInterface;

    /**
     * @param MainResourceInterface $mainResource
     * @return AttachmentInterface[]
     */
    public function getAttachmentsForMaileva(MainResourceInterface $mainResource): array;
}
