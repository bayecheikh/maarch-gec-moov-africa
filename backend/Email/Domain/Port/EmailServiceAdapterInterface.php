<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email Service Adapter Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Domain\Port;

use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface EmailServiceAdapterInterface
{
    /**
     * Initializes the email service using the given configuration.
     *
     * @param ConfigurationInterface $config Configuration in 'value' with the necessary details such as:
     *      - For PHPMailer: host, port, auth, etc.
     *      - For Microsoft Graph: tenantId, clientId, clientSecret, etc.
     * @param UserInterface $sendByUser User who sent the email
     *
     * @return void
     */
    public function initialize(ConfigurationInterface $config, UserInterface $sendByUser): void;

    /**
     * Sets the email sender details.
     *
     * @param string $email Sender's email address.
     * @param string|null $name Optional sender's name.
     * @return void
     */
    public function setSender(string $email, ?string $name = null): void;

    /**
     * Sets the email reply to details.
     *
     * @param string $email Reply to email address.
     * @return void
     */
    public function setReplyToEmail(string $email): void;

    /**
     * Sets the recipients of the email.
     *
     * @param array $to Array of "To" recipient email addresses.
     * @param array $cc Optional array of "CC" recipient email addresses.
     * @param array $bcc Optional array of "BCC" recipient email addresses.
     * @return void
     */
    public function setRecipients(array $to, array $cc = [], array $bcc = []): void;

    /**
     * Sets the subject of the email.
     *
     * @param string $subject Email subject.
     * @return void
     */
    public function setSubject(string $subject): void;

    /**
     * Sets the body of the email, with support for HTML or plain text.
     *
     * @param string $body Email body content.
     * @param bool $isHtml True if the body content is HTML, false otherwise.
     * @return void
     */
    public function setBody(string $body, bool $isHtml = true): void;

    /**
     * Adds attachments to the email.
     *
     * @param array $attachments An array of attachments where each item contains:
     *      - 'path': File path or encoded content.
     *      - 'name': File name.
     * @return void
     */
    public function addAttachments(array $attachments): void;

    /**
     * Sends the email.
     *
     * @return bool True if the email was sent successfully, false otherwise.
     */
    public function send(): bool;
}
