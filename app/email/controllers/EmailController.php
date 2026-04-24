<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email Controller
 * @author dev@maarch.org
 */

namespace Email\controllers;

use Attachment\models\AttachmentModel;
use Attachment\models\AttachmentTypeModel;
use Configuration\models\ConfigurationModel;
use Contact\models\ContactModel;
use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Email\models\EmailModel;
use Entity\models\EntityModel;
use Exception;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use History\models\HistoryModel;
use MaarchCourrier\Contact\Infrastructure\Repository\ContactRepository;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\Email\Domain\AdminEmailServerPrivilege;
use MaarchCourrier\Email\Domain\Email;
use MaarchCourrier\Email\Domain\EmailStatus;
use MaarchCourrier\Email\Infrastructure\Repository\EmailRepository;
use MaarchCourrier\Email\Infrastructure\SendEmailFactory;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use Note\models\NoteEntityModel;
use Note\models\NoteModel;
use Resource\controllers\ResController;
use Resource\controllers\ResourceListController;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Psr7\Request;
use SrcCore\controllers\LogsController;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class EmailController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function send(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'sendmail', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        $isSent = EmailController::createEmail(['userId' => $GLOBALS['id'], 'data' => $body]);

        if (!empty($isSent['errors'])) {
            $httpCode = empty($isSent['code']) ? 400 : $isSent['code'];
            return $response->withStatus($httpCode)->withJson(['errors' => $isSent['errors']]);
        }

        return $response->withJson(['id' => $isSent]);
    }

    /**
     * @param array $args
     * @return array|int
     * @throws Exception
     */
    public static function createEmail(array $args): array|int
    {
        ValidatorModel::notEmpty($args, ['userId', 'data']);
        ValidatorModel::intVal($args, ['userId']);
        ValidatorModel::arrayType($args, ['data', 'options']);

        $check = EmailController::controlCreateEmail([
            'userId'                   => $args['userId'],
            'data'                     => $args['data'],
            'isAcknowledgementReceipt' => !empty($args['options']['acknowledgementReceiptId'])
        ]);
        if (!empty($check['errors'])) {
            return ['errors' => $check['errors'], 'code' => $check['code']];
        }

        $configuration = ConfigurationModel::getByPrivilege([
            'privilege' => 'admin_email_server',
            'select'    => ['value']
        ]);
        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration)) {
            return ['errors' => 'Configuration is missing'];
        }

        $args['data']['sender']['replyTo'] = $args['data']['sender']['email'];

        if (!empty($configuration['useSMTPAuth'])) {
            $args['data']['sender']['email'] = $configuration['from'];
        }

        $contactRepository = new ContactRepository();
        $userRepository = new UserRepository();

        $recipientsEmails = $ccEmails = $cciEmails = [];

        foreach ($args['data']['recipients'] as $recipient) {
            $currentRecipient = $recipient;
            if (is_string($recipient)) {
                $currentRecipient = [
                    'type'  => 'email',
                    'email' => $recipient
                ];
            } elseif ($recipient['type'] == 'contact') {
                $currentRecipient['email'] = $contactRepository->getById($recipient['id'])->getEmail();
            } elseif ($recipient['type'] == 'user') {
                $currentRecipient['email'] = $userRepository->getUserById($recipient['id'])->getMail();
            }


            if (!empty($currentRecipient['email'])) {
                $recipientsEmails[] = $currentRecipient;
            }
        }

        if (!empty($args['data']['cc'])) {
            foreach ($args['data']['cc'] as $cc) {
                $currentCc = $cc;
                if (is_string($cc)) {
                    $currentCc = [
                        'type'  => 'email',
                        'email' => $cc
                    ];
                } elseif ($cc['type'] == 'contact') {
                    $currentCc['email'] = $contactRepository->getById($cc['id'])->getEmail();
                } elseif ($cc['type'] == 'user') {
                    $currentCc['email'] = $userRepository->getUserById($cc['id'])->getMail();
                }

                if (!empty($currentCc['email'])) {
                    $ccEmails[] = $currentCc;
                }
            }
        }

        if (!empty($args['data']['cci'])) {
            foreach ($args['data']['cci'] as $cci) {
                $currentCci = $cci;
                if (is_string($cci)) {
                    $currentCci = [
                        'type'  => 'email',
                        'email' => $cci
                    ];
                } elseif ($cci['type'] == 'contact') {
                    $currentCci['email'] = $contactRepository->getById($cci['id'])->getEmail();
                } elseif ($cci['type'] == 'user') {
                    $currentCci['email'] = $userRepository->getUserById($cci['id'])->getMail();
                }

                if (!empty($currentCci['email'])) {
                    $cciEmails[] = $currentCci;
                }
            }
        }

        $id = EmailModel::create([
            'userId'            => $args['userId'],
            'sender'            => empty($args['data']['sender']) ? '{}' : json_encode($args['data']['sender']),
            'recipients'        => empty($recipientsEmails) ? '[]' : json_encode($recipientsEmails),
            'cc'                => empty($ccEmails) ? '[]' : json_encode($ccEmails),
            'cci'               => empty($cciEmails) ? '[]' : json_encode($cciEmails),
            'object'            => empty($args['data']['object']) ? null : $args['data']['object'],
            'body'              => empty($args['data']['body']) ? null : $args['data']['body'],
            'document'          => empty($args['data']['document']) ? null : json_encode($args['data']['document']),
            'isHtml'            => $args['data']['isHtml'] ? 'true' : 'false',
            'status'            => $args['data']['status'] == 'DRAFT' ? 'DRAFT' : 'WAITING',
            'messageExchangeId' => empty($args['data']['messageExchangeId']) ? null : $args['data']['messageExchangeId']
        ]);

        $isSent = ['success' => 'success'];
        if ($args['data']['status'] != 'DRAFT') {
            if ($args['data']['status'] == 'EXPRESS') {
                $userRepository = new UserRepository();
                $user = $userRepository->getUserById($args['userId']);

                $email = (new Email())
                    ->setId($id)
                    ->setUser($user)
                    ->setSender($args['data']['sender'])
                    ->setRecipients($recipientsEmails)
                    ->setCc($ccEmails)
                    ->setCci($cciEmails)
                    ->setObject($args['data']['object'])
                    ->setBody($args['data']['body'])
                    ->setDocuments($args['data']['document'] ?? [])
                    ->setIsHtml($args['data']['isHtml'])
                    ->setStatus(EmailStatus::from($args['data']['status']))
                    ->setMessageExchangeId($args['data']['messageExchangeId'] ?? null);

                $logConfig = LogsController::getLogConfig();
                $logTypeInfo = LogsController::getLogType('logTechnique');
                $logger = LogsController::initMonologLogger(
                    $logConfig,
                    $logTypeInfo,
                    false,
                    CoreConfigModel::getCustomId()
                );

                $sendEmail = SendEmailFactory::create($logger);
                $serverEmailConfig = (new ConfigurationRepository())->getByPrivilege(new AdminEmailServerPrivilege());

                $sendEmail->setEmailServerConfig($serverEmailConfig);
                $didEmailSent = $sendEmail->execute($email);

                $emailRepository = new EmailRepository($userRepository);
                $status = $didEmailSent ? 'SENT' : 'ERROR';
                $emailRepository->updateEmail($email, ['status' => $status, 'send_date' => 'CURRENT_TIMESTAMP']);

                if (!$didEmailSent) {
                    $history = HistoryModel::get([
                        'select'  => ['info'],
                        'where'   => ['user_id = ?', 'event_id = ?', 'event_type = ?'],
                        'data'    => [$email->getUser()->getId(), 'sendEmail', 'ERROR'],
                        'orderBy' => ['event_date DESC'],
                        'limit'   => 1
                    ]);

                    $infoError = "Could not send email : {$history[0]['info']}";
                    $logger->error($infoError);
                    $isSent = ['errors' => $infoError];
                }

                if (
                    PrivilegeController::hasPrivilege([
                        'privilegeId' => 'admin_email_server',
                        'userId'      => $GLOBALS['id']
                    ])
                ) {
                    $online = !empty($isSent['success']) ? 'true' : 'false';
                    ConfigurationModel::update([
                        'postSet' => ['value' => "jsonb_set(value, '{online}', '$online')"],
                        'where'   => ['privilege = ?'],
                        'data'    => ['admin_email_server']
                    ]);
                }
            } else {
                $customId = CoreConfigModel::getCustomId();
                if (empty($customId)) {
                    $customId = 'null';
                }
                $encryptKey = CoreConfigModel::getEncryptKey();
                $options = empty($args['options']) ? '' : serialize($args['options']);
                $command = "php src/app/email/scripts/sendEmail.php ";
                $command .= "$customId $id {$args['userId']} '$encryptKey' '$options' > /dev/null &";
                exec($command);
            }
            if (!empty($isSent['success'])) {
                $info = _EMAIL_ADDED;

                if (!empty($configuration['useSMTPAuth'])) {
                    $info .= ' : ' . _SENDER_EMAIL_REPLACED_SMTP_SENDER;
                }

                HistoryController::add([
                    'tableName' => 'emails',
                    'recordId'  => $id,
                    'eventType' => 'ADD',
                    'eventId'   => 'emailCreation',
                    'info'      => $info
                ]);

                if (!empty($args['data']['document']['id'])) {
                    HistoryController::add([
                        'tableName' => 'res_letterbox',
                        'recordId'  => $args['data']['document']['id'],
                        'eventType' => 'ADD',
                        'eventId'   => 'emailCreation',
                        'info'      => $info
                    ]);
                }
            }
        } else {
            HistoryController::add([
                'tableName' => 'emails',
                'recordId'  => $id,
                'eventType' => 'ADD',
                'eventId'   => 'emailDraftCreation',
                'info'      => _EMAIL_DRAFT_SAVED
            ]);

            if (!empty($args['data']['document']['id'])) {
                HistoryController::add([
                    'tableName' => 'res_letterbox',
                    'recordId'  => $args['data']['document']['id'],
                    'eventType' => 'ADD',
                    'eventId'   => 'emailDraftCreation',
                    'info'      => _EMAIL_DRAFT_SAVED
                ]);
            }
        }

        if (!empty($isSent['errors'])) {
            return $isSent;
        }

        return $id;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function getById(Request $request, Response $response, array $args): Response
    {
        $rawEmail = EmailModel::getById(['id' => $args['id']]);

        if (empty($rawEmail)) {
            return $response->withStatus(403)->withJson(['errors' => 'Email not found']);
        }

        if (is_null($rawEmail['document'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Email not linked to a resource']);
        }

        $document = json_decode($rawEmail['document'], true);

        if (!ResController::hasRightByResId(['resId' => [$document['id']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        if (!empty($document['isLinked'])) {
            $resource = ResModel::getById([
                'resId'  => $document['id'],
                'select' => ['alt_identifier', 'subject', 'typist', 'format', 'filesize', 'version']
            ]);
            $size = null;
            if (empty($document['original'])) {
                $convertedResource = AdrModel::getDocuments([
                    'select'  => ['docserver_id', 'path', 'filename'],
                    'where'   => ['res_id = ?', 'type in (?)', 'version = ?'],
                    'data'    => [$document['id'], ['PDF', 'SIGN'], $resource['version']],
                    'orderBy' => ["type='SIGN' DESC"],
                    'limit'   => 1
                ]);

                if (!empty($convertedResource[0])) {
                    $docserver = DocserverModel::getByDocserverId([
                        'docserverId' => $convertedResource[0]['docserver_id'],
                        'select'      => ['path_template']
                    ]);
                    $pathToDocument = $docserver['path_template'] .
                        str_replace('#', DIRECTORY_SEPARATOR, $convertedResource[0]['path']) .
                        $convertedResource[0]['filename'];
                    if (file_exists($pathToDocument)) {
                        $size = StoreController::getFormattedSizeFromBytes(['size' => filesize($pathToDocument)]);
                    }
                }
            } else {
                $size = StoreController::getFormattedSizeFromBytes(['size' => $resource['filesize']]);
            }

            $document['resource'] = [
                'id'      => $document['id'],
                'chrono'  => $resource['alt_identifier'],
                'label'   => $resource['subject'],
                'creator' => UserModel::getLabelledUserById(['id' => $resource['typist']]),
                'format'  => $resource['format'],
                'size'    => $size
            ];
        }
        if (!empty($document['attachments'])) {
            foreach ($document['attachments'] as $key => $attachment) {
                $attachmentInfo = AttachmentModel::getById([
                    'id'     => $attachment['id'],
                    'select' => ['title', 'format', 'filesize']
                ]);

                $size = null;
                if (empty($attachment['original'])) {
                    $convertedResource = AdrModel::getAttachments([
                        'select'  => ['docserver_id', 'path', 'filename'],
                        'where'   => ['res_id = ?', 'type = ?'],
                        'data'    => [$attachment['id'], 'PDF'],
                        'orderBy' => ["type='SIGN' DESC"],
                        'limit'   => 1
                    ]);

                    if (!empty($convertedResource[0])) {
                        $docserver = DocserverModel::getByDocserverId([
                            'docserverId' => $convertedResource[0]['docserver_id'],
                            'select'      => ['path_template']
                        ]);
                        $pathToDocument = $docserver['path_template'] .
                            str_replace('#', DIRECTORY_SEPARATOR, $convertedResource[0]['path']) .
                            $convertedResource[0]['filename'];
                        if (file_exists($pathToDocument)) {
                            $size = StoreController::getFormattedSizeFromBytes(['size' => filesize($pathToDocument)]);
                            $document['attachments'][$key]['format'] = 'PDF';
                        }
                    }
                } else {
                    $document['attachments'][$key]['format'] = $attachmentInfo['format'];
                    $size = StoreController::getFormattedSizeFromBytes(['size' => $attachmentInfo['filesize']]);
                }

                $document['attachments'][$key]['label'] = $attachmentInfo['title'];
                $document['attachments'][$key]['size'] = $size;
            }
        }
        if (!empty($document['notes'])) {
            $notes = NoteModel::get([
                'select' => ['id', 'note_text', 'user_id'],
                'where'  => ['id in (?)'],
                'data'   => [$document['notes']]
            ]);
            $notes = array_column($notes, null, 'id');
            foreach ($document['notes'] as $key => $noteId) {
                $document['notes'][$key] = [
                    'id'        => $noteId,
                    'label'     => $notes[$noteId]['note_text'],
                    'typeLabel' => 'note',
                    'creator'   => UserModel::getLabelledUserById(['id' => $notes[$noteId]['user_id']]),
                    'format'    => 'pdf',
                    'size'      => null
                ];
            }
        }

        $sender = json_decode($rawEmail['sender'], true);
        $entityLabel = null;
        if (!empty($sender['entityId'])) {
            $entityLabel = EntityModel::getById(['select' => ['entity_label'], 'id' => $sender['entityId']]);
            $entityLabel = $entityLabel['entity_label'];
        }
        $sender['label'] = $entityLabel;

        $email = [
            'id'           => $rawEmail['id'],
            'sender'       => $sender,
            'recipients'   => json_decode($rawEmail['recipients'], true),
            'cc'           => json_decode($rawEmail['cc'], true),
            'cci'          => json_decode($rawEmail['cci'], true),
            'userId'       => $rawEmail['user_id'],
            'object'       => $rawEmail['object'],
            'body'         => $rawEmail['body'],
            'isHtml'       => $rawEmail['is_html'],
            'status'       => $rawEmail['status'],
            'creationDate' => $rawEmail['creation_date'],
            'sendDate'     => $rawEmail['send_date'],
            'document'     => $document
        ];

        //Nettoyage et enrichissement des données pour la confidentialité
        $email = $this->reformatEmailReturnUsingConfidentiality($email);

        return $response->withJson($email);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        $check = EmailController::controlCreateEmail(['userId' => $GLOBALS['id'], 'data' => $body]);
        if (!empty($check['errors'])) {
            $httpCode = isset($check['code']) ? (int)$check['code'] : 400;
            return $response->withStatus($httpCode)->withJson(['errors' => $check['errors']]);
        }

        $configuration = ConfigurationModel::getByPrivilege([
            'privilege' => 'admin_email_server',
            'select'    => ['value']
        ]);
        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration)) {
            return $response->withStatus(400)->withJson(['errors' => "Configuration is missing"]);
        }

        $body['sender']['replyTo'] = $body['sender']['email'];

        if (!empty($configuration['useSMTPAuth'])) {
            $body['sender']['email'] = $configuration['from'];
        }

        EmailModel::update([
            'set'   => [
                'sender'     => empty($body['sender']) ? '{}' : json_encode($body['sender']),
                'recipients' => empty($body['recipients']) ? '[]' : json_encode($body['recipients']),
                'cc'         => empty($body['cc']) ? '[]' : json_encode($body['cc']),
                'cci'        => empty($body['cci']) ? '[]' : json_encode($body['cci']),
                'object'     => empty($body['object']) ? null : $body['object'],
                'body'       => empty($body['body']) ? null : $body['body'],
                'document'   => empty($body['document']) ? null : json_encode($body['document']),
                'is_html'    => $body['isHtml'] ? 'true' : 'false',
                'status'     => $body['status'] == 'DRAFT' ? 'DRAFT' : 'WAITING'
            ],
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        if ($body['status'] != 'DRAFT') {
            $customId = CoreConfigModel::getCustomId();
            if (empty($customId)) {
                $customId = 'null';
            }
            $encryptKey = CoreConfigModel::getEncryptKey();
            $command = "php src/app/email/scripts/sendEmail.php ";
            $command .= "{$customId} {$args['id']} {$GLOBALS['id']} '{$encryptKey}' > /dev/null &";
            exec($command);

            HistoryController::add([
                'tableName' => 'emails',
                'recordId'  => $args['id'],
                'eventType' => 'ADD',
                'eventId'   => 'emailCreation',
                'info'      => _EMAIL_ADDED
            ]);

            if (!empty($body['document']['id'])) {
                HistoryController::add([
                    'tableName' => 'res_letterbox',
                    'recordId'  => $body['document']['id'],
                    'eventType' => 'ADD',
                    'eventId'   => 'emailCreation',
                    'info'      => _EMAIL_ADDED
                ]);
            }
        } else {
            $info = _EMAIL_UPDATED;

            if (!empty($configuration['useSMTPAuth'])) {
                $info .= ' : ' . _SENDER_EMAIL_REPLACED_SMTP_SENDER;
            }

            HistoryController::add([
                'tableName' => 'emails',
                'recordId'  => $args['id'],
                'eventType' => 'UP',
                'eventId'   => 'emailModification',
                'info'      => $info
            ]);

            if (!empty($body['document']['id'])) {
                HistoryController::add([
                    'tableName' => 'res_letterbox',
                    'recordId'  => $body['document']['id'],
                    'eventType' => 'UP',
                    'eventId'   => 'emailModification',
                    'info'      => $info
                ]);
            }
        }

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $email = EmailModel::getById(['select' => ['user_id', 'document'], 'id' => $args['id']]);
        if (empty($email)) {
            return $response->withStatus(400)->withJson(['errors' => 'Email does not exist']);
        }
        if ($email['user_id'] != $GLOBALS['id']) {
            return $response->withStatus(403)->withJson(['errors' => 'Email out of perimeter']);
        }

        EmailModel::delete([
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        HistoryController::add([
            'tableName' => 'emails',
            'recordId'  => $args['id'],
            'eventType' => 'DEL',
            'eventId'   => 'emailDeletion',
            'info'      => _EMAIL_REMOVED
        ]);

        if (!empty($email['document'])) {
            $document = json_decode($email['document'], true);

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $document['id'],
                'eventType' => 'DEL',
                'eventId'   => 'emailDeletion',
                'info'      => _EMAIL_REMOVED
            ]);
        }

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function getByResId(Request $request, Response $response, array $args): Response
    {
        if (
            !Validator::intVal()->validate($args['resId']) ||
            !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])
        ) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $queryParams = $request->getQueryParams();
        if (!empty($queryParams['limit']) && !Validator::intVal()->validate($queryParams['limit'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query limit is not an int value']);
        }

        $where = ["document->>'id' = ?", "(status != 'DRAFT' or (status = 'DRAFT' and user_id = ?))"];

        if (!empty($queryParams['type'])) {
            if (!Validator::stringType()->validate($queryParams['type'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Query type is not a string value']);
            }

            if ($queryParams['type'] == 'ar') {
                $where[] = "object LIKE '[AR]%'";
            } elseif ($queryParams['type'] == 'm2m') {
                $where[] = 'message_exchange_id is not null';
            } elseif ($queryParams['type'] == 'email') {
                $where[] = "(object NOT LIKE '[AR]%' OR object is null)";
                $where[] = 'message_exchange_id is null';
            }
        }

        $limit = null;
        if (!empty($queryParams['limit'])) {
            $limit = (int)$queryParams['limit'];
        }

        $emails = EmailModel::get([
            'select' => ['*'],
            'where'  => $where,
            'data'   => [$args['resId'], $GLOBALS['id']],
            'limit'  => $limit
        ]);

        foreach ($emails as $key => $email) {
            $emails[$key]['sender'] = json_decode($email['sender']);
            $emails[$key]['recipients'] = json_decode($email['recipients'], true);
            $emails[$key]['cc'] = json_decode($email['cc'], true);
            $emails[$key]['cci'] = json_decode($email['cci'], true);
            $emails[$key]['document'] = json_decode($email['document']);
            $emails[$key] = $this->reformatEmailReturnUsingConfidentiality($emails[$key]);
        }

        return $response->withJson(['emails' => $emails]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function getAvailableEmails(Request $request, Response $response): Response
    {
        $availableEmails = EmailController::getAvailableEmailsByUserId(['userId' => $GLOBALS['id']]);
        return $response->withJson(['emails' => $availableEmails]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function getInitializationByResId(Request $request, Response $response, array $args): Response
    {
        if (
            !Validator::intVal()->validate($args['resId']) ||
            !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])
        ) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $resource = ResModel::getById([
            'select' => ['filename', 'version', 'alt_identifier', 'subject', 'typist', 'format', 'filesize'],
            'resId'  => $args['resId']
        ]);
        if (empty($resource)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        $document = [];
        if (!empty($resource['filename'])) {
            $convertedResource = AdrModel::getDocuments([
                'select'  => ['docserver_id', 'path', 'filename', 'type'],
                'where'   => ['res_id = ?', 'type in (?)', 'version = ?'],
                'data'    => [$args['resId'], ['PDF', 'SIGN'], $resource['version']],
                'orderBy' => ["type='SIGN' DESC"],
                'limit'   => 1
            ]);
            $convertedDocument = null;
            $isSigned = false;
            if (!empty($convertedResource[0])) {
                $docserver = DocserverModel::getByDocserverId([
                    'docserverId' => $convertedResource[0]['docserver_id'],
                    'select'      => ['path_template']
                ]);
                $pathToDocument = $docserver['path_template'] .
                    str_replace('#', DIRECTORY_SEPARATOR, $convertedResource[0]['path']) .
                    $convertedResource[0]['filename'];
                if (file_exists($pathToDocument)) {
                    $convertedDocument = [
                        'size' => StoreController::getFormattedSizeFromBytes(['size' => filesize($pathToDocument)])
                    ];
                }

                $isSigned = ($convertedResource[0]['type'] === 'SIGN');
            }

            $document = [
                'id'                => $args['resId'],
                'chrono'            => $resource['alt_identifier'],
                'label'             => $resource['subject'],
                'convertedDocument' => $convertedDocument,
                'creator'           => UserModel::getLabelledUserById(['id' => $resource['typist']]),
                'format'            => $resource['format'],
                'size'              => StoreController::getFormattedSizeFromBytes(['size' => $resource['filesize']]),
                'isSigned'          => $isSigned
            ];
        }

        $attachments = [];
        $attachmentTypes = AttachmentTypeModel::get(['select' => ['type_id', 'label', 'email_link']]);
        $attachmentTypes = array_column($attachmentTypes, null, 'type_id');
        $rawAttachments = AttachmentModel::get([
            'select' => [
                'res_id',
                'title',
                'identifier',
                'attachment_type',
                'typist',
                'format',
                'filesize',
                'status',
                'recipient_id',
                'recipient_type'
            ],
            'where'  => ['res_id_master = ?', 'attachment_type not in (?)', 'status not in (?)'],
            'data'   => [$args['resId'], ['signed_response'], ['DEL', 'OBS']]
        ]);
        foreach ($rawAttachments as $attachment) {
            $attachmentId = $attachment['res_id'];
            $signedAttachment = AttachmentModel::get([
                'select' => ['res_id'],
                'where'  => ['origin = ?', 'status != ?', 'attachment_type = ?'],
                'data'   => ["{$attachmentId},res_attachments", 'DEL', 'signed_response']
            ]);
            if (!empty($signedAttachment[0])) {
                $attachmentId = $signedAttachment[0]['res_id'];
            }

            $convertedAttachment = AdrModel::getAttachments([
                'select' => ['docserver_id', 'path', 'filename'],
                'where'  => ['res_id = ?', 'type = ?'],
                'data'   => [$attachmentId, 'PDF'],
            ]);
            $convertedDocument = null;
            if (!empty($convertedAttachment[0])) {
                $docserver = DocserverModel::getByDocserverId([
                    'docserverId' => $convertedAttachment[0]['docserver_id'],
                    'select'      => ['path_template']
                ]);
                $pathToDocument = $docserver['path_template'] .
                    str_replace('#', DIRECTORY_SEPARATOR, $convertedAttachment[0]['path']) .
                    $convertedAttachment[0]['filename'];
                if (file_exists($pathToDocument)) {
                    $convertedDocument = [
                        'size' => StoreController::getFormattedSizeFromBytes(['size' => filesize($pathToDocument)])
                    ];
                }
            }

            $attachments[] = [
                'id'                => $attachmentId,
                'chrono'            => $attachment['identifier'],
                'label'             => $attachment['title'],
                'typeLabel'         => $attachmentTypes[$attachment['attachment_type']]['label'],
                'attachInMail'      => $attachmentTypes[$attachment['attachment_type']]['email_link'],
                'convertedDocument' => $convertedDocument,
                'creator'           => UserModel::getLabelledUserById(['id' => $attachment['typist']]),
                'format'            => $attachment['format'],
                'size'              => StoreController::getFormattedSizeFromBytes(['size' => $attachment['filesize']]),
                'status'            => $attachment['status'],
                'recipientId'       => $attachment['recipient_id'],
                'recipientType'     => $attachment['recipient_type']
            ];
        }

        $notes = [];
        $userEntities = EntityModel::getByUserId(['userId' => $GLOBALS['id'], 'select' => ['entity_id']]);
        $userEntities = array_column($userEntities, 'entity_id');
        $rawNotes = NoteModel::get([
            'select' => ['id', 'note_text', 'user_id'],
            'where'  => ['identifier = ?'],
            'data'   => [$args['resId']]
        ]);
        foreach ($rawNotes as $rawNote) {
            $allowed = false;
            if ($rawNote['user_id'] == $GLOBALS['id']) {
                $allowed = true;
            } else {
                $noteEntities = NoteEntityModel::get([
                    'select' => ['item_id'],
                    'where'  => ['note_id = ?'],
                    'data'   => [$rawNote['id']]
                ]);
                if (!empty($noteEntities)) {
                    foreach ($noteEntities as $noteEntity) {
                        if (in_array($noteEntity['item_id'], $userEntities)) {
                            $allowed = true;
                            break;
                        }
                    }
                } else {
                    $allowed = true;
                }
            }
            if ($allowed) {
                $notes[] = [
                    'id'        => $rawNote['id'],
                    'label'     => $rawNote['note_text'],
                    'typeLabel' => 'note',
                    'creator'   => UserModel::getLabelledUserById(['id' => $rawNote['user_id']]),
                    'format'    => 'pdf',
                    'size'      => null
                ];
            }
        }

        return $response->withJson(['resource' => $document, 'attachments' => $attachments, 'notes' => $notes]);
    }

    /**
     * @param array $args
     * @return array[]
     * @throws Exception
     */
    public static function getAvailableEmailsByUserId(array $args): array
    {
        $currentUser = UserModel::getById([
            'select' => ['firstname', 'lastname', 'mail', 'user_id'],
            'id'     => $args['userId']
        ]);

        $availableEmails = [
            [
                'entityId' => null,
                'label'    => $currentUser['firstname'] . ' ' . $currentUser['lastname'],
                'email'    => $currentUser['mail']
            ]
        ];

        if (PrivilegeController::hasPrivilege(['privilegeId' => 'use_mail_services', 'userId' => $args['userId']])) {
            $entities = EntityModel::getWithUserEntities([
                'select' => ['entities.entity_label', 'entities.email', 'entities.entity_id', 'entities.id'],
                'where'  => ['users_entities.user_id = ?'],
                'data'   => [$args['userId']]
            ]);

            foreach ($entities as $entity) {
                if (!empty($entity['email'])) {
                    $availableEmails[] = [
                        'entityId' => $entity['id'],
                        'label'    => $entity['entity_label'],
                        'email'    => $entity['email']
                    ];
                }
            }

            $emailsEntities = CoreConfigModel::getXmlLoaded(['path' => 'config/externalMailsEntities.xml']);
            if (!empty($emailsEntities)) {
                $userEntities = array_column($entities, 'entity_id');
                foreach ($emailsEntities->externalEntityMail as $entityMail) {
                    $entityId = (string)$entityMail->targetEntityId;

                    if (empty($entityId)) {
                        $availableEmails[] = [
                            'entityId' => null,
                            'label'    => (string)$entityMail->defaultName,
                            'email'    => trim((string)$entityMail->EntityMail)
                        ];
                    } elseif (in_array($entityId, $userEntities)) {
                        $entity = EntityModel::getByEntityId([
                            'select'   => ['entity_label', 'id'],
                            'entityId' => $entityId
                        ]);

                        if (!empty($entity)) {
                            $availableEmails[] = [
                                'entityId' => $entity['id'],
                                'label'    => $entity['entity_label'],
                                'email'    => trim((string)$entityMail->EntityMail)
                            ];
                        }
                    }
                }
            }
        }

        return $availableEmails;
    }

    /**
     * @param array $args
     * @return array|string[]
     * @throws Exception
     */
    private static function controlCreateEmail(array $args): array
    {
        ValidatorModel::notEmpty($args, ['userId']);
        ValidatorModel::intVal($args, ['userId']);
        ValidatorModel::arrayType($args, ['data']);

        if (!Validator::stringType()->notEmpty()->validate($args['data']['status'])) {
            return ['errors' => 'Data status is not a string or empty', 'code' => 400];
        } elseif (
            $args['data']['status'] != 'DRAFT' &&
            (
                !Validator::arrayType()->notEmpty()->validate($args['data']['sender']) ||
                !Validator::stringType()->notEmpty()->validate($args['data']['sender']['email'])
            )
        ) {
            return ['errors' => 'Data sender email is not set', 'code' => 400];
        } elseif (
            $args['data']['status'] != 'DRAFT' &&
            !Validator::arrayType()->notEmpty()->validate($args['data']['recipients'])
        ) {
            return ['errors' => 'Data recipients is not an array or empty', 'code' => 400];
        } elseif (!Validator::boolType()->validate($args['data']['isHtml'])) {
            return ['errors' => 'Data isHtml is not a boolean or empty', 'code' => 400];
        }

        if (
            !empty($args['data']['object']) &&
            !Validator::stringType()->length(1, 255)->validate($args['data']['object'])
        ) {
            return ['errors' => 'Data object is not a string or is more than 255 characters', 'code' => 400];
        }

        if (!empty($args['data']['sender']['email']) && empty($args['isAcknowledgementReceipt'])) {
            $configuration = ConfigurationModel::getByPrivilege([
                'privilege' => 'admin_email_server',
                'select'    => ['value']
            ]);
            $configuration = json_decode($configuration['value'], true);

            $availableEmails = EmailController::getAvailableEmailsByUserId(['userId' => $args['userId']]);
            $emails = array_column($availableEmails, 'email');
            if (!empty($configuration['from'])) {
                $emails[] = $configuration['from'];
            }
            if (!in_array($args['data']['sender']['email'], $emails)) {
                return ['errors' => 'Data sender email is not allowed', 'code' => 400];
            }
            if (!empty($args['data']['sender']['entityId'])) {
                $entities = array_column($availableEmails, 'entityId');
                if (!in_array($args['data']['sender']['entityId'], $entities)) {
                    return ['errors' => 'Data sender entityId is not allowed', 'code' => 400];
                }
            }
        }

        if (!empty($args['data']['document']) && !empty($args['data']['document']['id'])) {
            $check = Validator::notEmpty()->intVal()->validate($args['data']['document']['id']);
            $check = $check && Validator::boolType()->validate($args['data']['document']['isLinked']);
            $check = $check && Validator::boolType()->validate($args['data']['document']['original']);
            if (!$check) {
                return ['errors' => 'Data document errors', 'code' => 400];
            }
            if (
                !ResController::hasRightByResId([
                    'resId'  => [$args['data']['document']['id']],
                    'userId' => $args['userId']
                ])
            ) {
                return ['errors' => 'Document out of perimeter', 'code' => 403];
            }
            if (!ResourceListController::controlFingerprints(['resId' => $args['data']['document']['id']])) {
                return ['errors' => 'Document has fingerprints which do not match', 'code' => 400];
            }
            if (!empty($args['data']['document']['attachments'])) {
                if (!is_array($args['data']['document']['attachments'])) {
                    return ['errors' => 'Data document[attachments] is not an array', 'code' => 400];
                }
                foreach ($args['data']['document']['attachments'] as $attachment) {
                    $check = Validator::notEmpty()->intVal()->validate($attachment['id']);
                    $check = $check && Validator::boolType()->validate($attachment['original']);
                    if (!$check) {
                        return ['errors' => 'Data document[attachments] errors', 'code' => 400];
                    }
                    $checkAttachment = AttachmentModel::getById([
                        'id'     => $attachment['id'],
                        'select' => ['res_id_master']
                    ]);
                    if (
                        empty($checkAttachment) || $checkAttachment['res_id_master'] != $args['data']['document']['id']
                    ) {
                        return ['errors' => 'Attachment out of perimeter', 'code' => 403];
                    }
                }
            }
            if (!empty($args['data']['document']['notes'])) {
                if (!is_array($args['data']['document']['notes'])) {
                    return ['errors' => 'Data document[notes] is not an array', 'code' => 400];
                }
                foreach ($args['data']['document']['notes'] as $note) {
                    if (!Validator::notEmpty()->intVal()->validate($note)) {
                        return ['errors' => 'Data document[notes] errors', 'code' => 400];
                    }
                    $checkNote = NoteModel::getById(['id' => $note, 'select' => ['identifier', 'user_id']]);
                    if (empty($checkNote) || $checkNote['identifier'] != $args['data']['document']['id']) {
                        return ['errors' => 'Note out of perimeter', 'code' => 403];
                    } elseif ($checkNote['user_id'] == $args['userId']) {
                        continue;
                    }
                    $rawUserEntities = EntityModel::getByUserId([
                        'userId' => $args['userId'],
                        'select' => ['entity_id']
                    ]);
                    $userEntities = [];
                    foreach ($rawUserEntities as $rawUserEntity) {
                        $userEntities[] = $rawUserEntity['entity_id'];
                    }
                    $noteEntities = NoteEntityModel::get([
                        'select' => ['item_id'],
                        'where'  => ['note_id = ?'],
                        'data'   => [$note]
                    ]);
                    if (!empty($noteEntities)) {
                        $found = false;
                        foreach ($noteEntities as $noteEntity) {
                            if (in_array($noteEntity['item_id'], $userEntities)) {
                                $found = true;
                            }
                        }
                        if (!$found) {
                            return ['errors' => 'Note out of perimeter', 'code' => 403];
                        }
                    }
                }
            }
        }

        return ['success' => 'success'];
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public static function getHostname(): ?string
    {
        $hostname = null;
        $maarchUrl = CoreConfigModel::getApplicationUrl();
        if (!empty($maarchUrl)) {
            $hostname = parse_url($maarchUrl, PHP_URL_HOST);
        }

        $fileConfig = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
        if (!empty($fileConfig['config']['externalSmtpHostname'])) {
            $hostname = $fileConfig['config']['externalSmtpHostname'];
        }

        return $hostname;
    }

    /**
     * @param array $email
     * @return array
     * @throws Exception
     */
    private function reformatEmailReturnUsingConfidentiality(array $email): array
    {
        $returnEmail = $email;

        $canViewConfidentialContactInformation = PrivilegeController::hasPrivilege([
            'privilegeId' => 'view_confidential_contact_information',
            'userId'      => $GLOBALS['id']
        ]);

        foreach ($email['recipients'] as $key => $recipient) {
            if ($recipient['type'] == 'user') {
                $returnEmail['recipients'][$key]['labelToDisplay'] =
                    UserModel::getLabelledUserById(['id' => $recipient['id']]);
            } elseif ($recipient['type'] == 'contact') {
                $contact = ContactModel::getById([
                    'select' => ['firstname', 'lastname', 'company', 'is_confidential'],
                    'id'     => $recipient['id']
                ]);

                $isConfidentialContact = !empty($contact['is_confidential']);
                $returnEmail['recipients'][$key]['email'] = [
                    'confidential' => $isConfidentialContact
                ];
                if (!$isConfidentialContact || $canViewConfidentialContactInformation) {
                    $returnEmail['recipients'][$key]['email']['value'] = $recipient['email'];
                }

                $returnEmail['recipients'][$key]['labelToDisplay'] = (!empty($contact['lastname']))
                    ? $contact['firstname'] . ' ' . $contact['lastname']
                    : $contact['company'];
            } elseif ($recipient['type'] == 'entity') {
                $entityLabel = EntityModel::getById(['select' => ['entity_label'], 'id' => $recipient['id']]);
                $returnEmail['recipients'][$key]['labelToDisplay'] = $entityLabel['entity_label'];
            }
        }

        foreach ($email['cc'] as $key => $recipient) {
            if ($recipient['type'] == 'user') {
                $returnEmail['cc'][$key]['labelToDisplay'] =
                    UserModel::getLabelledUserById(['id' => $recipient['id']]);
            } elseif ($recipient['type'] == 'contact') {
                $contact = ContactModel::getById([
                    'select' => ['firstname', 'lastname', 'company', 'is_confidential'],
                    'id'     => $recipient['id']
                ]);

                $isConfidentialContact = !empty($contact['is_confidential']);
                ;
                $returnEmail['cc'][$key]['email'] = [
                    'confidential' => $isConfidentialContact
                ];
                if (!$isConfidentialContact || $canViewConfidentialContactInformation) {
                    $returnEmail['cc'][$key]['email']['value'] = $recipient['email'];
                }

                $returnEmail['cc'][$key]['labelToDisplay'] = (!empty($contact['lastname']))
                    ? $contact['firstname'] . ' ' . $contact['lastname']
                    : $contact['company'];
            } elseif ($recipient['type'] == 'entity') {
                $entityLabel = EntityModel::getById(['select' => ['entity_label'], 'id' => $recipient['id']]);
                $returnEmail['cc'][$key]['labelToDisplay'] = $entityLabel['entity_label'];
            }
        }

        foreach ($email['cci'] as $key => $recipient) {
            if ($recipient['type'] == 'user') {
                $returnEmail['cci'][$key]['labelToDisplay'] =
                    UserModel::getLabelledUserById(['id' => $recipient['id']]);
            } elseif ($recipient['type'] == 'contact') {
                $contact = ContactModel::getById([
                    'select' => ['firstname', 'lastname', 'company', 'is_confidential'],
                    'id'     => $recipient['id']
                ]);

                $isConfidentialContact = !empty($contact['is_confidential']);
                $returnEmail['cci'][$key]['email'] = [
                    'confidential' => $isConfidentialContact
                ];
                if (!$isConfidentialContact || $canViewConfidentialContactInformation) {
                    $returnEmail['cci'][$key]['email']['value'] = $recipient['email'];
                }

                $returnEmail['cci'][$key]['labelToDisplay'] = (!empty($contact['lastname']))
                    ? $contact['firstname'] . ' ' . $contact['lastname']
                    : $contact['company'];
            } elseif ($recipient['type'] == 'entity') {
                $entityLabel = EntityModel::getById(['select' => ['entity_label'], 'id' => $recipient['id']]);
                $returnEmail['cci'][$key]['labelToDisplay'] = $entityLabel['entity_label'];
            }
        }

        return $returnEmail;
    }
}
