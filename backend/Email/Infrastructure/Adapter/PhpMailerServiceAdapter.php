<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief PhpMailer Service Adapter
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Email\Infrastructure\Adapter;

use DOMDocument;
use Exception;
use History\controllers\HistoryController;
use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\Email\Domain\Port\EmailServiceAdapterInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Psr\Log\LoggerInterface;
use SrcCore\controllers\PasswordController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\TextFormatModel;

class PhpMailerServiceAdapter implements EmailServiceAdapterInterface
{
    private PHPMailer $phpMailer;
    private UserInterface $sendByUser;

    public function __construct(private readonly LoggerInterface $logger)
    {
        $this->phpMailer = new PHPMailer();
        $this->phpMailer->XMailer = null; // To remove the header X-Mailer
    }

    /**
     * Initializes the email service using the given configuration.
     *
     * @param ConfigurationInterface $config Configuration in 'value' with the necessary details such as:
     *       - For PHPMailer: host, port, auth, etc.
     *       - For Microsoft Graph: tenantId, clientId, clientSecret, etc.
     * @param UserInterface $sendByUser User who sent the email
     *
     * @throws Exception
     */
    public function initialize(ConfigurationInterface $config, UserInterface $sendByUser): void
    {
        $this->sendByUser = $sendByUser;

        $this->clearAllMailerData();

        if (in_array($config->getValue()['type'], ['smtp', 'mail'])) {
            if ($config->getValue()['type'] == 'smtp') {
                $this->phpMailer->isSMTP();
            } elseif ($config->getValue()['type'] == 'mail') {
                $this->phpMailer->isMail();
            }

            $maarchUrl = CoreConfigModel::getApplicationUrl();
            if (!empty($maarchUrl)) {
                $this->phpMailer->Hostname = parse_url($maarchUrl, PHP_URL_HOST);
            }

            $smarthost = $config->getValue()['smarthost'] ?? null;
            if (!empty($smarthost)) {
                $this->phpMailer->Hostname = $smarthost;
            }

            $this->phpMailer->Host = $config->getValue()['host'];
            $this->phpMailer->Port = $config->getValue()['port'];
            $this->phpMailer->SMTPAutoTLS = false;
            if (!empty($config->getValue()['secure'])) {
                $this->phpMailer->SMTPSecure = $config->getValue()['secure'];
            }
            $this->phpMailer->SMTPAuth = $config->getValue()['auth'];
            if ($config->getValue()['auth']) {
                $this->phpMailer->Username = $config->getValue()['user'];
                if (!empty($config->getValue()['password'])) {
                    $this->phpMailer->Password = PasswordController::decrypt([
                        'encryptedData' => $config->getValue()['password']
                    ]);
                }
            }
        } elseif ($config->getValue()['type'] == 'sendmail') {
            $this->phpMailer->isSendmail();
        } elseif ($config->getValue()['type'] == 'qmail') {
            $this->phpMailer->isQmail();
        }

        $this->phpMailer->CharSet = $config->getValue()['charset'];

        $this->phpMailer->Timeout = 30;
        $this->phpMailer->SMTPDebug = SMTP::DEBUG_CLIENT;
        $this->phpMailer->Debugoutput = function ($str) {
            if (str_contains($str, 'SMTP ERROR')) {
                $this->logger->error("PhpMailerServiceAdapter - SMTP Error : $str");
                HistoryController::add([
                    'tableName' => 'emails',
                    'recordId'  => 'email',
                    'eventType' => 'ERROR',
                    'eventId'   => 'PhpMailerServiceAdapter - sendEmail',
                    'userId'    => $this->sendByUser->getId(),
                    'info'      => $str
                ]);
            }
        };
    }

    private function clearAllMailerData(): void
    {
        $this->phpMailer->clearAddresses(); //Clears all recipients assigned in the TO array.
        $this->phpMailer->clearCCs(); //Clears all recipients assigned in the CC array.
        $this->phpMailer->clearBCCs(); //Clears all recipients assigned in the BCC array.
        $this->phpMailer->clearReplyTos(); //Clears all recipients assigned in the ReplyTo array.
        $this->phpMailer->clearAllRecipients(); //Clears all recipients assigned in the TO, CC, and BCC arrays.
        $this->phpMailer->clearAttachments(); //Clears all previously set filesystem, string, and binary attachments.
        $this->phpMailer->clearCustomHeaders(); //Clears all custom headers.
    }

    /**
     * Sets the email reply to details.
     *
     * @param string $email Reply to email address.
     *
     * @return void
     * @throws Exception
     */
    public function setReplyToEmail(string $email): void
    {
        if (!empty($email)) {
            $this->phpMailer->addReplyTo($email);
        }
    }

    /**
     * @throws Exception
     */
    public function setSender(string $email, ?string $name = null): void
    {
        $setFrom = '';
        if (!empty($name)) {
            $setFrom = TextFormatModel::normalize(['string' => $name]);
            $setFrom = ucwords($setFrom);
        }
        $this->phpMailer->setFrom($email, $setFrom);
    }

    /**
     * @throws Exception
     */
    public function setRecipients(array $to, array $cc = [], array $bcc = []): void
    {
        foreach ($to as $recipient) {
            $this->phpMailer->addAddress($recipient);
        }
        foreach ($cc as $recipient) {
            $this->phpMailer->addCC($recipient);
        }
        foreach ($bcc as $recipient) {
            $this->phpMailer->addBCC($recipient);
        }
    }

    public function setSubject(string $subject): void
    {
        $this->phpMailer->Subject = $subject;
    }

    public function setBody(string $body, bool $isHtml = true): void
    {
        if ($isHtml && !empty($body)) {
            $dom = new DOMDocument();
            $internalErrors = libxml_use_internal_errors(true);
            $dom->loadHTML($body, LIBXML_NOWARNING);
            libxml_use_internal_errors($internalErrors);
            $images = $dom->getElementsByTagName('img');

            foreach ($images as $key => $image) {
                $originalSrc = $image->getAttribute('src');
                if (preg_match('/^data:image\/(\w+);base64,/', $originalSrc)) {
                    $encodedImage = substr($originalSrc, strpos($originalSrc, ',') + 1);
                    $imageFormat = substr($originalSrc, 11, strpos($originalSrc, ';') - 11);
                    $this->phpMailer->addStringEmbeddedImage(
                        base64_decode($encodedImage),
                        "embeded$key",
                        "embeded$key.$imageFormat"
                    );
                    $body = str_replace($originalSrc, "cid:embeded$key", $body);
                }
            }
        }
        if (empty($body)) {
            $this->phpMailer->AllowEmpty = true;
        }
        $this->phpMailer->isHTML($isHtml);
        $this->phpMailer->Body = $body;
    }

    /**
     * @throws Exception
     */
    public function addAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (!empty($attachment['fileContent'] ?? null) && !empty($attachment['filename'] ?? null)) {
                $this->phpMailer->addStringAttachment(
                    $attachment['fileContent'],
                    $attachment['filename']
                );
            } elseif (!empty($attachment['path'] ?? null)) {
                $this->phpMailer->addAttachment(
                    $attachment['path'],
                    $attachment['filename'] ?? basename($attachment['path'])
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function send(): bool
    {
        try {
            if ($this->phpMailer->send()) {
                $this->logger->info("PhpMailerServiceAdapter - send - onRejected: Email sent successfully");
                return true;
            } else {
                $msg = "PhpMailerServiceAdapter - did not send: " . $this->phpMailer->ErrorInfo;
                $this->logger->error($msg);
                HistoryController::add([
                    'tableName' => 'emails',
                    'recordId'  => 'email',
                    'eventType' => 'ERROR',
                    'eventId'   => 'PhpMailerServiceAdapter - send',
                    'info'      => $msg
                ]);
                return false;
            }
        } catch (Exception $e) {
            $this->logger->error("PhpMailerServiceAdapter - send - exception: {$e->getMessage()}", $e->getTrace());
            return false;
        }
    }
}
