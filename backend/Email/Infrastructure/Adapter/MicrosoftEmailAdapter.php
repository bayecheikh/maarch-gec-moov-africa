<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Microsoft Email Service Adapter
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Infrastructure\Adapter;

use Exception;
use GuzzleHttp\Psr7\Utils;
use History\controllers\HistoryController;
use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\DocumentStorage\Domain\Port\FileSystemServiceInterface;
use MaarchCourrier\Email\Domain\Port\EmailServiceAdapterInterface;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\FileAttachment;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\Recipient;
use Microsoft\Graph\Generated\Users\Item\SendMail\SendMailPostRequestBody;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Psr\Log\LoggerInterface;
use ReflectionException;
use SrcCore\controllers\PasswordController;
use SrcCore\models\TextFormatModel;
use Throwable;

class MicrosoftEmailAdapter implements EmailServiceAdapterInterface
{
    private GraphServiceClient $graphClient;
    private Message $message;
    private string $sendFrom;
    private UserInterface $sendByUser;

    public function __construct(
        private readonly FileSystemServiceInterface $fileSystemService,
        private readonly LoggerInterface $logger
    ) {
        $this->message = new Message();
    }

    /**
     * Initializes the email service using the given configuration.
     *
     * @param ConfigurationInterface $config Configuration in 'value' with the necessary details such as:
     *       - For PHPMailer: host, port, auth, etc.
     * @param UserInterface $sendByUser User who sent the email
     *
     * @throws Exception
     */
    public function initialize(ConfigurationInterface $config, UserInterface $sendByUser): void
    {
        $this->sendByUser = $sendByUser;
        $this->sendFrom = $config->getValue()['from'];
        $tokenRequestContext = new ClientCredentialContext(
            PasswordController::decrypt(['encryptedData' => $config->getValue()['tenantId']]),
            PasswordController::decrypt(['encryptedData' => $config->getValue()['clientId']]),
            PasswordController::decrypt(['encryptedData' => $config->getValue()['clientSecret']])
        );
        $this->graphClient = new GraphServiceClient($tokenRequestContext, ['https://graph.microsoft.com/.default']);
    }

    /**
     * Sets the email sender details.
     *
     * @param string $email Sender's email address.
     * @param string|null $name Optional sender's name.
     *
     * @return void
     * @throws Exception
     */
    public function setSender(string $email, ?string $name = null): void
    {
        $emailAddress = new EmailAddress();
        $emailAddress->setAddress($email);
        if (!empty($name)) {
            $setFrom = TextFormatModel::normalize(['string' => $name]);
            $emailAddress->setName(ucwords($setFrom));
        }
        $sender = new Recipient();
        $sender->setEmailAddress($emailAddress);
        $this->message->setFrom($sender);
    }

    public function setReplyToEmail(string $email): void
    {
        if (!empty($email)) {
            $emailAddress = new EmailAddress();
            $emailAddress->setAddress($email);
            $replyTo = new Recipient();
            $replyTo->setEmailAddress($emailAddress);
            $this->message->setReplyTo([$replyTo]);
        }
    }

    /**
     * Sets the recipients of the email.
     *
     * @param array $to Array of "To" recipient email addresses.
     * @param array $cc Optional array of "CC" recipient email addresses.
     * @param array $bcc Optional array of "BCC" recipient email addresses.
     * @return void
     */
    public function setRecipients(array $to, array $cc = [], array $bcc = []): void
    {
        $this->message->setToRecipients($this->formatRecipients($to));
        $this->message->setCcRecipients($this->formatRecipients($cc));
        $this->message->setBccRecipients($this->formatRecipients($bcc));
    }

    private function formatRecipients(array $emails): array
    {
        $recipients = [];
        foreach ($emails as $email) {
            $recipient = new Recipient();
            $emailAddress = new EmailAddress();
            $emailAddress->setAddress($email);
            $recipient->setEmailAddress($emailAddress);
            $recipients[] = $recipient;
        }
        return $recipients;
    }

    /**
     * Sets the subject of the email.
     *
     * @param string $subject Email subject.
     * @return void
     */
    public function setSubject(string $subject): void
    {
        $this->message->setSubject($subject);
    }

    /**
     * Sets the body of the email, with support for HTML or plain text.
     *
     * @param string $body Email body content.
     * @param bool $isHtml True if the body content is HTML, false otherwise.
     *
     * @return void
     * @throws ReflectionException
     */
    public function setBody(string $body, bool $isHtml = true): void
    {
        $itemBody = new ItemBody();
        $itemBody->setContentType(new BodyType($isHtml ? 'HTML' : 'TEXT'));
        $itemBody->setContent($body);
        $this->message->setBody($itemBody);
    }

    /**
     * Adds attachments to the email.
     *
     * @param array $attachments An array of attachments where each item can contain:
     *      - 'fileContent': Encoded content.
     *      - 'filename': File name.
     * @return void
     */
    public function addAttachments(array $attachments): void
    {
        $this->message->setAttachments($this->formatAttachments($attachments));
    }

    private function formatAttachments(array $attachments): array
    {
        $formattedAttachments = [];
        foreach ($attachments as $attachment) {
            $fileAttachment = new FileAttachment();

            if (!empty($attachment['path'] ?? null)) {
                $fileAttachment->setName($attachment['filename'] ?? basename($attachment['path']));
                $fileAttachment->setContentType(mime_content_type($attachment['path']));
                $fileContent = file_get_contents($attachment['path']);
                $stream = Utils::streamFor(base64_encode($fileContent));
                $fileAttachment->setContentBytes($stream);
                $formattedAttachments[] = $fileAttachment;
            } elseif (!empty($attachment['fileContent'] ?? null) && !empty($attachment['filename'] ?? null)) {
                $fileAttachment->setName(basename($attachment['filename']));
                $fileAttachment->setContentType(
                    $this->fileSystemService->getFileMimeTypeByContent($attachment['fileContent']) ?: null
                );
                $stream = Utils::streamFor(base64_encode($attachment['fileContent']));
                $fileAttachment->setContentBytes($stream);
                $formattedAttachments[] = $fileAttachment;
            }
        }
        return $formattedAttachments;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function send(): bool
    {
        try {
            $requestBody = new SendMailPostRequestBody();
            $requestBody->setMessage($this->message);
            $requestBody->setSaveToSentItems(true);
            return $this->graphClient->users()->byUserId($this->sendFrom)->sendMail()->post($requestBody)->then(
                function (): bool {
                    $this->logger->info("MicrosoftEmailAdapter - send: Email sent successfully");
                    return true;
                },
                function (Throwable $th): bool {
                    $msg = "MicrosoftEmailAdapter - sendEmail : " . ($th->getMessage() ?: 'Unknown error');
                    $this->logger->error($msg, $th->getTrace());
                    HistoryController::add([
                        'tableName' => 'emails',
                        'recordId'  => 'email',
                        'eventType' => 'ERROR',
                        'eventId'   => 'MicrosoftEmailAdapter - send - onRejected',
                        'userId'    => $this->sendByUser->getId(),
                        'info'      => $msg
                    ]);
                    return false;
                }
            )->wait();
        } catch (Throwable $th) {
            HistoryController::add([
                'tableName' => 'emails',
                'recordId'  => 'email',
                'eventType' => 'ERROR',
                'eventId'   => 'MicrosoftEmailAdapter - send',
                'userId'    => $this->sendByUser->getId(),
                'info'      => $th->getMessage()
            ]);
            $this->logger->error("MicrosoftEmailAdapter - send - Throwable: {$th->getMessage()}", $th->getTrace());
            return false;
        }
    }
}
