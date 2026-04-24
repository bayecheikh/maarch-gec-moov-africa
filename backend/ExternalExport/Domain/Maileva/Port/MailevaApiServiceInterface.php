<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Api Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Domain\Maileva\Port;

use MaarchCourrier\Core\Domain\Contact\Port\ContactInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\MailevaTemplate;

interface MailevaApiServiceInterface
{
    public function setConfig(array $mailevaConfig, MailevaTemplate $template): void;

    public function getAuthToken(): void;

    public function createSending(array $body): string;

    public function setDocumentForSending(string $sendingId, string $resourceName, string $fileContent): void;

    public function setRecipientForSending(string $sendingId, ContactInterface $recipient): array;

    public function setSendingOptions(string $sendingId, array $options): void;

    public function send(string $sendingId): void;

    public function deleteSending(string $sendingId): void;

    public function getEreSenders(): array;

    public function downloadDepositProof(string $sendingId, string $recipientId): array;

    public function downloadProofOfReceipt(string $sendingId, string $recipientId): array;
}
