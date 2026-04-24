<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Send Message Exchange Review Controller
 * @author dev@maarch.org
 */

namespace MessageExchange\controllers;

use Attachment\models\AttachmentModel;
use Attachment\models\AttachmentTypeModel;
use Contact\models\ContactModel;
use DateTime;
use Docserver\models\DocserverModel;
use Doctype\models\DoctypeModel;
use Entity\models\EntityModel;
use Exception;
use ExportSeda\controllers\SendMessageController;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use MessageExchange\models\MessageExchangeModel;
use Note\models\NoteModel;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use SrcCore\controllers\PasswordController;
use SrcCore\models\TextFormatModel;
use Status\models\StatusModel;
use stdClass;
use User\models\UserModel;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class SendMessageExchangeController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function getInitialization(Request $request, Response $response): Response
    {
        $rawEntities = EntityModel::getWithUserEntities([
            'select' => ['entities.id', 'entities.entity_label', 'entities.business_id'],
            'where'  => ['users_entities.user_id = ?', 'business_id is not null', 'business_id != ?'],
            'data'   => [$GLOBALS['id'], '']
        ]);

        $entities = [];
        foreach ($rawEntities as $entity) {
            $entities[] = [
                'id'    => $entity['id'],
                'label' => $entity['entity_label'],
                'm2m'   => $entity['business_id']
            ];
        }

        return $response->withJson(['entities' => $entities]);
    }

    /**
     * @param array $args
     * @return array|Exception[]|string[]
     */
    public static function saveMessageExchange(array $args = []): array
    {
        $dataObject = $args['dataObject'];
        $oData = new stdClass();
        $oData->messageId = MessageExchangeModel::generateUniqueId();
        $oData->date = $dataObject->Date;

        $oData->MessageIdentifier = new stdClass();
        $oData->MessageIdentifier->value = $dataObject->MessageIdentifier->value;

        $oData->TransferringAgency = new stdClass();
        $oData->TransferringAgency->Identifier = new stdClass();
        $oData->TransferringAgency->Identifier->value = $dataObject->TransferringAgency->Identifier->value;

        $oData->ArchivalAgency = new stdClass();
        $oData->ArchivalAgency->Identifier = new stdClass();
        $oData->ArchivalAgency->Identifier->value = $dataObject->ArchivalAgency->Identifier->value;

        $oData->archivalAgreement = new stdClass();
        $oData->archivalAgreement->value = "";

        $replyCode = "";
        if (!empty($dataObject->ReplyCode)) {
            $replyCode = $dataObject->ReplyCode;
        }

        $oData->replyCode = new stdClass();
        $oData->replyCode = $replyCode;

        $dataObject = self::cleanBase64Value(['dataObject' => $dataObject]);

        $aDataExtension = [
            'status'            => 'W',
            'fullMessageObject' => $dataObject,
            'resIdMaster'       => $args['res_id_master'],
            'SenderOrgNAme'     => $dataObject
                ->TransferringAgency
                ->OrganizationDescriptiveMetadata
                ->Contact[0]
                ->DepartmentName,
            'RecipientOrgNAme'  => $dataObject->ArchivalAgency->OrganizationDescriptiveMetadata->Name,
            'filePath'          => $args['file_path'],
        ];

        $user = UserModel::getByLogin(['login' => $args['userId'], 'select' => ['id']]);
        $messageId = MessageExchangeModel::insertMessage([
            "data"          => $oData,
            "type"          => $args['type'],
            "dataExtension" => $aDataExtension,
            "userId"        => $user['id']
        ]);

        return $messageId;
    }

    /**
     * @param array $args
     * @return mixed
     */
    protected static function cleanBase64Value(array $args = []): mixed
    {
        $dataObject = $args['dataObject'];
        $aCleanDataObject = [];
        if (!empty($dataObject->DataObjectPackage->BinaryDataObject)) {
            foreach ($dataObject->DataObjectPackage->BinaryDataObject as $key => $value) {
                $value->Attachment->value = "";
                $aCleanDataObject[$key] = $value;
            }
            $dataObject->DataObjectPackage->BinaryDataObject = $aCleanDataObject;
        }
        return $dataObject;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public static function createMessageExchange(Request $request, Response $response, array $args): Response
    {
        if (
            !PrivilegeController::hasPrivilege(['privilegeId' => 'manage_numeric_package', 'userId' => $GLOBALS['id']])
        ) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (
            !Validator::intVal()->validate($args['resId']) ||
            !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])
        ) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $body = $request->getParsedBody();
        $errors = self::control($body);

        if (!empty($errors)) {
            return $response->withStatus(400)->withJson(['errors' => $errors]);
        }

        /***************** GET MAIL INFOS *****************/
        $AllUserEntities = EntityModel::getWithUserEntities(
            ['where' => ['user_id = ?', 'business_id != \'\''], 'data' => [$GLOBALS['id']]]
        );

        foreach ($AllUserEntities as $value) {
            if ($value['id'] == $body['senderEmail']) {
                $TransferringAgencyInformations = $value;
                break;
            }
        }

        if (empty($TransferringAgencyInformations)) {
            return $response->withStatus(400)->withJson(['errors' => "no sender"]);
        }

        $AllInfoMainMail = ResModel::getById(['select' => ['*'], 'resId' => $args['resId']]);
        if (!empty($AllInfoMainMail['type_id'])) {
            $doctype = DoctypeModel::getById(['select' => ['description'], 'id' => $AllInfoMainMail['type_id']]);
        }

        $tmpMainExchangeDoc = explode("__", $body['mainExchangeDoc']);
        $MainExchangeDoc = ['tablename' => $tmpMainExchangeDoc[0], 'res_id' => $tmpMainExchangeDoc[1]];

        $fileInfo = [];
        if (!empty($body['joinFile']) || $MainExchangeDoc['tablename'] == 'res_letterbox') {
            $AllInfoMainMail['Title'] = $AllInfoMainMail['subject'];
            $AllInfoMainMail['OriginatingAgencyArchiveUnitIdentifier'] = $AllInfoMainMail['alt_identifier'];
            $AllInfoMainMail['DocumentType'] = $doctype['description'] ?? null;
            $AllInfoMainMail['tablenameExchangeMessage'] = 'res_letterbox';
            $fileInfo = [$AllInfoMainMail];
        }

        if ($MainExchangeDoc['tablename'] == 'res_attachments') {
            $body['joinAttachment'][] = $MainExchangeDoc['res_id'];
        }

        /**************** GET ATTACHMENTS INFOS ***************/
        $AttachmentsInfo = [];
        if (!empty($body['joinAttachment'])) {
            $AttachmentsInfo = AttachmentModel::get(
                ['select' => ['*'], 'where' => ['res_id in (?)'], 'data' => [$body['joinAttachment']]]
            );
            $attachmentTypes = AttachmentTypeModel::get(['select' => ['type_id', 'label']]);
            $attachmentTypes = array_column($attachmentTypes, 'label', 'type_id');
            foreach ($AttachmentsInfo as $key => $value) {
                $AttachmentsInfo[$key]['Title'] = $value['title'];
                $AttachmentsInfo[$key]['OriginatingAgencyArchiveUnitIdentifier'] = $value['identifier'];
                $AttachmentsInfo[$key]['DocumentType'] = $attachmentTypes[$value['attachment_type']];
                $AttachmentsInfo[$key]['tablenameExchangeMessage'] = 'res_attachments';
            }
        }
        $aAllAttachment = $AttachmentsInfo;

        /******************* GET NOTE INFOS **********************/
        $aComments = self::generateComments([
            'resId'                          => $args['resId'],
            'notes'                          => $body['notes'],
            'body'                           => $body['content'],
            'TransferringAgencyInformations' => $TransferringAgencyInformations
        ]);

        /*********** ORDER ATTACHMENTS IN MAIL ***************/
        if ($MainExchangeDoc['tablename'] == 'res_letterbox') {
            $mainDocument = $fileInfo;
            $aMergeAttachment = array_merge($fileInfo, $aAllAttachment);
        } else {
            $mainDocument = [];
            $firstAttachment = [];

            foreach ($aAllAttachment as $key => $value) {
                if (
                    $value['res_id'] == $MainExchangeDoc['res_id'] &&
                    $MainExchangeDoc['tablename'] == $value['tablenameExchangeMessage']
                ) {
                    if ($AllInfoMainMail['category_id'] == 'outgoing') {
                        $aOutgoingMailInfo = $AllInfoMainMail;
                        $aOutgoingMailInfo['Title'] = $AllInfoMainMail['subject'];
                        $aOutgoingMailInfo['OriginatingAgencyArchiveUnitIdentifier'] =
                            $AllInfoMainMail['alt_identifier'];
                        $aOutgoingMailInfo['DocumentType'] = $AllInfoMainMail['type_label'];
                        $aOutgoingMailInfo['tablenameExchangeMessage'] = $AllInfoMainMail['tablenameExchangeMessage'];
                        $mainDocument = [$aOutgoingMailInfo];
                    } else {
                        $mainDocument = [$aAllAttachment[$key]];
                    }
                    $firstAttachment = [$aAllAttachment[$key]];
                    unset($aAllAttachment[$key]);
                }
            }
            if (!empty($fileInfo[0]['filename'])) {
                $aMergeAttachment = array_merge($firstAttachment, $fileInfo, $aAllAttachment);
            } else {
                $aMergeAttachment = array_merge($firstAttachment, $aAllAttachment);
            }
        }

        $mainDocument[0]['Title'] = '[CAPTUREM2M]' . $body['object'];

        foreach ($body['contacts'] as $contactId) {
            /******** GET ARCHIVAl INFORMATIONs **************/
            $communicationType = ContactModel::getById(['select' => ['communication_means'], 'id' => $contactId]);
            $aArchivalAgencyCommunicationType = json_decode($communicationType['communication_means'], true);
            $ArchivalAgencyCommunicationType = [];

            if (!empty($aArchivalAgencyCommunicationType)) {
                if (!empty($aArchivalAgencyCommunicationType['email'])) {
                    $ArchivalAgencyCommunicationType['type'] = 'email';
                    $ArchivalAgencyCommunicationType['value'] = $aArchivalAgencyCommunicationType['email'];
                } else {
                    $ArchivalAgencyCommunicationType['type'] = 'url';
                    $ArchivalAgencyCommunicationType['value'] = rtrim(
                        $aArchivalAgencyCommunicationType['url'],
                        "/"
                    );
                    if (strrpos($ArchivalAgencyCommunicationType['value'], "http://") !== false) {
                        $prefix = "http://";
                    } elseif (strrpos($ArchivalAgencyCommunicationType['value'], "https://") !== false) {
                        $prefix = "https://";
                    } else {
                        return $response->withStatus(403)->withJson(['errors' => 'http or https missing']);
                    }
                    $url = str_replace($prefix, '', $ArchivalAgencyCommunicationType['value']);
                    $login = $aArchivalAgencyCommunicationType['login'] ?? '';
                    $password = !empty($aArchivalAgencyCommunicationType['password']) ? PasswordController::decrypt(
                        ['encryptedData' => $aArchivalAgencyCommunicationType['password']]
                    ) : '';
                    $ArchivalAgencyCommunicationType['value'] = $prefix;
                    if (!empty($login) && !empty($password)) {
                        $ArchivalAgencyCommunicationType['value'] .= $login . ':' . $password . '@';
                    }
                    $ArchivalAgencyCommunicationType['value'] .= $url;
                }
            }
            $ArchivalAgencyContactInformations = ContactModel::getById(['select' => ['*'], 'id' => $contactId]);

            /******** GENERATE MESSAGE EXCHANGE OBJECT *********/
            $dataObject = self::generateMessageObject([
                'Comment'              => $aComments,
                'ArchivalAgency'       => [
                    'CommunicationType'   => $ArchivalAgencyCommunicationType,
                    'ContactInformations' => $ArchivalAgencyContactInformations
                ],
                'TransferringAgency'   => [
                    'EntitiesInformations' => $TransferringAgencyInformations
                ],
                'attachment'           => $aMergeAttachment,
                'res'                  => $mainDocument,
                'mainExchangeDocument' => $MainExchangeDoc
            ]);
            /******** GENERATION DU BORDEREAU */
            $filePath = SendMessageController::generateMessageFile(
                ['messageObject' => $dataObject, 'type' => 'ArchiveTransfer']
            );

            /******** SAVE MESSAGE *********/
            $messageExchangeReturn = self::saveMessageExchange(
                [
                    'dataObject'    => $dataObject,
                    'res_id_master' => $args['resId'],
                    'file_path'     => $filePath,
                    'type'          => 'ArchiveTransfer',
                    'userId'        => $GLOBALS['login']
                ]
            );
            if (!empty($messageExchangeReturn['error'])) {
                return $response->withStatus(400)->withJson(['errors' => $messageExchangeReturn['error']]);
            } else {
                $messageId = $messageExchangeReturn['messageId'];
            }
            self::saveUnitIdentifier(
                ['attachment' => $aMergeAttachment, 'notes' => $body['notes'], 'messageId' => $messageId]
            );

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $args['resId'],
                'eventType' => 'UP',
                'eventId'   => 'resup',
                'info'      => _NUMERIC_PACKAGE_ADDED . _ON_DOC_NUM
                    . $args['resId'] . ' (' . $messageId . ') : "' .
                    TextFormatModel::cutString(['string' => $mainDocument[0]['Title'], 'max' => 254])
            ]);

            HistoryController::add([
                'tableName' => 'message_exchange',
                'recordId'  => $messageId,
                'eventType' => 'ADD',
                'eventId'   => 'messageexchangeadd',
                'info'      => _NUMERIC_PACKAGE_ADDED . ' (' . $messageId . ')'
            ]);

            /******** ENVOI *******/
            $res = SendMessageController::send($dataObject, $messageId, 'ArchiveTransfer');

            if ($res['status'] == 1) {
                $errors = [];
                $errors[] = "L'envoi a échoué";
                $errors[] = $res['content'];
                return $response->withStatus(400)->withJson(['errors' => $errors]);
            }
        }

        return $response->withStatus(200);
    }

    /**
     * @param array $args
     * @return array
     */
    protected static function control(array $args = []): array
    {
        $errors = [];

        if (empty($args['mainExchangeDoc'])) {
            $errors[] = 'wrong format for mainExchangeDoc';
        }

        if (empty($args['object'])) {
            $errors[] = 'Body object is empty';
        }

        if (empty($args['joinFile']) && empty($args['joinAttachment']) && empty($args['mainExchangeDoc'])) {
            $errors[] = 'no attachment';
        }

        if (empty($args['contacts']) || !is_array($args['contacts'])) {
            $errors[] = 'Body contacts is empty or not an array';
        }
        foreach ($args['contacts'] as $key => $contact) {
            if (empty($contact)) {
                $errors[] = "Body contacts[{$key}] is empty";
                break;
            }
        }

        if (empty($args['senderEmail'])) {
            $errors[] = 'Body senderEmail is empty';
        }

        return $errors;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    protected static function generateComments(array $args = []): array
    {
        $aReturn = [];

        $oBody = new stdClass();
        if (!empty($args['body'])) {
            $entityRoot = EntityModel::getEntityRootById(
                ['entityId' => $args['TransferringAgencyInformations']['entity_id']]
            );
            $userInfo = UserModel::getById(['id' => $GLOBALS['id'], 'select' => ['firstname', 'lastname', 'mail']]);
            $headerNote = $userInfo['firstname'] . ' ' . $userInfo['lastname'] . ' (' . $entityRoot['entity_label'] .
                ' - ' . $args['TransferringAgencyInformations']['entity_label'] . ' - ' . $userInfo['mail'] . ') : ';
            $oBody->value = $headerNote . ' ' . $args['body'];
        } else {
            $oBody->value = '';
        }
        $aReturn[] = $oBody;

        if (!empty($args['notes'])) {
            $notes = NoteModel::getByUserIdForResource([
                'select' => ['id', 'user_id', 'creation_date', 'note_text'],
                'resId'  => $args['resId'],
                'userId' => $GLOBALS['id']
            ]);

            if (!empty($notes)) {
                foreach ($notes as $value) {
                    if (!in_array($value['id'], $args['notes'])) {
                        continue;
                    }

                    $oComment = new stdClass();
                    $date = new DateTime($value['creation_date']);
                    $additionalUserInfos = '';
                    $userInfo = UserModel::getPrimaryEntityById([
                        'select' => [
                            'users.firstname',
                            'users.lastname',
                            'entities.entity_id',
                            'entities.entity_label'
                        ],
                        'id'     => $GLOBALS['id']
                    ]);
                    if (!empty($userInfo['entity_id'])) {
                        $entityRoot = EntityModel::getEntityRootById(['entityId' => $userInfo['entity_id']]);
                        $additionalUserInfos = ' (' . $entityRoot['entity_label'] . ' - ' . $userInfo['entity_label'] .
                            ')';
                    }
                    $oComment->value = $userInfo['firstname'] . ' ' . $userInfo['lastname'] . ' - ' .
                        $date->format('d-m-Y H:i:s') . $additionalUserInfos . ' : ' . $value['note_text'];
                    $aReturn[] = $oComment;
                }
            }
        }
        return $aReturn;
    }

    /**
     * @param array $args
     * @return stdClass
     * @throws Exception
     */
    public static function generateMessageObject(array $args = []): stdClass
    {
        $date = new DateTime();

        $messageObject = new stdClass();
        $messageObject->Comment = $args['Comment'];
        $messageObject->Date = $date->format(DateTime::ATOM);

        $messageObject->MessageIdentifier = new stdClass();
        $messageObject->MessageIdentifier->value = 'ArchiveTransfer_' .
            date("Ymd_His") . '_' . $GLOBALS['login'];

        /********* BINARY DATA OBJECT PACKAGE *********/
        $messageObject->DataObjectPackage = new stdClass();
        $messageObject->DataObjectPackage->BinaryDataObject = self::getBinaryDataObject($args['attachment']);

        /********* DESCRIPTIVE META DATA *********/
        $messageObject->DataObjectPackage->DescriptiveMetadata = self::getDescriptiveMetaDataObject($args);

        /********* ARCHIVAL AGENCY *********/
        $messageObject->ArchivalAgency = self::getArchivalAgencyObject(['ArchivalAgency' => $args['ArchivalAgency']]);

        /********* TRANSFERRING AGENCY *********/
        $channelType = $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->Channel;
        $messageObject->TransferringAgency = self::getTransferringAgencyObject(
            ['TransferringAgency' => $args['TransferringAgency'], 'ChannelType' => $channelType]
        );

        return $messageObject;
    }

    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getBinaryDataObject(array $args = []): array
    {
        $aReturn = [];

        foreach ($args as $key => $value) {
            if (!empty($value['filename'])) {
                if (!empty($value['tablenameExchangeMessage'])) {
                    $binaryDataObjectId = $value['tablenameExchangeMessage'] . "_" . $key . "_" . $value['res_id'];
                } else {
                    $binaryDataObjectId = $value['res_id'];
                }

                $binaryDataObject = new stdClass();
                $binaryDataObject->id = $binaryDataObjectId;

                $binaryDataObject->MessageDigest = new stdClass();
                $binaryDataObject->MessageDigest->value = $value['fingerprint'];
                $binaryDataObject->MessageDigest->algorithm = "sha256";

                $binaryDataObject->Size = $value['filesize'];

                $uri = str_replace("##", DIRECTORY_SEPARATOR, $value['path']);
                $uri = str_replace("#", DIRECTORY_SEPARATOR, $uri);

                $docServers = DocserverModel::getByDocserverId(['docserverId' => $value['docserver_id']]);
                $binaryDataObject->Attachment = new stdClass();
                $binaryDataObject->Attachment->uri = '';
                $binaryDataObject->Attachment->filename = basename($value['filename']);
                $binaryDataObject->Attachment->value = base64_encode(
                    file_get_contents($docServers['path_template'] . $uri . '/' . $value['filename'])
                );

                $binaryDataObject->FormatIdentification = new stdClass();
                $binaryDataObject->FormatIdentification->MimeType = mime_content_type(
                    $docServers['path_template'] . $uri . $value['filename']
                );

                $aReturn[] = $binaryDataObject;
            }
        }

        return $aReturn;
    }

    /**
     * @param array $args
     * @return stdClass
     */
    public static function getDescriptiveMetaDataObject(array $args = []): stdClass
    {
        $DescriptiveMetadataObject = new stdClass();
        $DescriptiveMetadataObject->ArchiveUnit = [];

        $documentArchiveUnit = new stdClass();
        $documentArchiveUnit->id = 'mail_1';

        $documentArchiveUnit->Content = self::getContent([
            'DescriptionLevel'                       => 'File',
            'Title'                                  => $args['res'][0]['Title'],
            'OriginatingSystemId'                    => $args['res'][0]['res_id'],
            'OriginatingAgencyArchiveUnitIdentifier' => $args['res'][0]['OriginatingAgencyArchiveUnitIdentifier'],
            'DocumentType'                           => $args['res'][0]['DocumentType'],
            'Status'                                 => $args['res'][0]['status'],
            'Writer'                                 => $args['res'][0]['typist'],
            'CreatedDate'                            => $args['res'][0]['creation_date'],
        ]);

        $documentArchiveUnit->ArchiveUnit = [];
        foreach ($args['attachment'] as $key => $value) {
            $attachmentArchiveUnit = new stdClass();
            $attachmentArchiveUnit->id = 'archiveUnit_' . $value['tablenameExchangeMessage'] . "_" . $key . "_" .
                $value['res_id'];
            $attachmentArchiveUnit->Content = self::getContent([
                'DescriptionLevel'                       => 'Item',
                'Title'                                  => $value['Title'],
                'OriginatingSystemId'                    => $value['res_id'],
                'OriginatingAgencyArchiveUnitIdentifier' => $value['OriginatingAgencyArchiveUnitIdentifier'],
                'DocumentType'                           => $value['DocumentType'],
                'Status'                                 => $value['status'],
                'Writer'                                 => $value['typist'],
                'CreatedDate'                            => $value['creation_date'],
            ]);
            $dataObjectReference = new stdClass();
            $dataObjectReference->DataObjectReferenceId = $value['tablenameExchangeMessage'] . '_' . $key . '_' .
                $value['res_id'];
            $attachmentArchiveUnit->DataObjectReference = [$dataObjectReference];

            $documentArchiveUnit->ArchiveUnit[] = $attachmentArchiveUnit;
        }
        $DescriptiveMetadataObject->ArchiveUnit[] = $documentArchiveUnit;

        return $DescriptiveMetadataObject;
    }

    /**
     * @param array $args
     * @return stdClass
     */
    public static function getContent(array $args = []): stdClass
    {
        $contentObject = new stdClass();
        $contentObject->DescriptionLevel = $args['DescriptionLevel'];
        $contentObject->Title = [$args['Title']];
        $contentObject->OriginatingSystemId = $args['OriginatingSystemId'];
        $contentObject->OriginatingAgencyArchiveUnitIdentifier = $args['OriginatingAgencyArchiveUnitIdentifier'];
        $contentObject->DocumentType = $args['DocumentType'];
        $contentObject->Status = StatusModel::getById(['id' => $args['Status']])['label_status'];

        if (!empty($args['Writer'])) {
            if (is_numeric($args['Writer'])) {
                $userInfos = UserModel::getById(['id' => $args['Writer'], 'select' => ['firstname', 'lastname']]);
            } else {
                $userInfos = UserModel::getByLogin(
                    ['login' => $args['Writer'], 'select' => ['firstname', 'lastname']]
                );
            }
        } else {
            $userInfos = ['firstname' => '', 'lastname' => ''];
        }

        $writer = new stdClass();
        $writer->FirstName = $userInfos['firstname'];
        $writer->BirthName = $userInfos['lastname'];
        $contentObject->Writer = [$writer];

        $contentObject->CreatedDate = date("Y-m-d", strtotime($args['CreatedDate']));

        return $contentObject;
    }

    /**
     * @param array $args
     * @return stdClass
     */
    public static function getArchivalAgencyObject(array $args = []): stdClass
    {
        $archivalAgencyObject = new stdClass();
        $archivalAgencyObject->Identifier = new stdClass();
        $externalId = json_decode($args['ArchivalAgency']['ContactInformations']['external_id'], true);
        $archivalAgencyObject->Identifier->value = $externalId['m2m'];

        $archivalAgencyObject->OrganizationDescriptiveMetadata = new stdClass();
        $archivalAgencyObject->OrganizationDescriptiveMetadata->Name = trim(
            $args['ArchivalAgency']['ContactInformations']['company'] . ' ' .
            $args['ArchivalAgency']['ContactInformations']['lastname'] . ' ' .
            $args['ArchivalAgency']['ContactInformations']['firstname']
        );

        if (isset($args['ArchivalAgency']['CommunicationType']['type'])) {
            $arcCommunicationObject = new stdClass();
            $arcCommunicationObject->Channel = $args['ArchivalAgency']['CommunicationType']['type'];
            $arcCommunicationObject->value = $args['ArchivalAgency']['CommunicationType']['value'];
            if ($args['ArchivalAgency']['CommunicationType']['type'] == 'url') {
                $postUrl = '/rest/saveNumericPackage';
                $arcCommunicationObject->value .= $postUrl;
            }

            $archivalAgencyObject->OrganizationDescriptiveMetadata->Communication = [$arcCommunicationObject];
        }

        $contactObject = new stdClass();
        $contactObject->DepartmentName = $args['ArchivalAgency']['ContactInformations']['department'];
        $contactObject->PersonName = $args['ArchivalAgency']['ContactInformations']['lastname'] . " " .
            $args['ArchivalAgency']['ContactInformations']['firstname'];

        $addressObject = new stdClass();
        $addressObject->CityName = $args['ArchivalAgency']['ContactInformations']['address_town'];
        $addressObject->Country = $args['ArchivalAgency']['ContactInformations']['address_country'];
        $addressObject->Postcode = $args['ArchivalAgency']['ContactInformations']['address_postcode'];
        $addressObject->PostOfficeBox = $args['ArchivalAgency']['ContactInformations']['address_number'];
        $addressObject->StreetName = $args['ArchivalAgency']['ContactInformations']['address_street'];

        $contactObject->Address = [$addressObject];

        $communicationContactPhoneObject = new stdClass();
        $communicationContactPhoneObject->Channel = 'phone';
        $communicationContactPhoneObject->value = $args['ArchivalAgency']['ContactInformations']['phone'];

        $communicationContactEmailObject = new stdClass();
        $communicationContactEmailObject->Channel = 'email';
        $communicationContactEmailObject->value = $args['ArchivalAgency']['ContactInformations']['email'];

        $contactObject->Communication = [$communicationContactPhoneObject, $communicationContactEmailObject];

        $archivalAgencyObject->OrganizationDescriptiveMetadata->Contact = [$contactObject];

        return $archivalAgencyObject;
    }

    /**
     * @param array $args
     * @return stdClass
     * @throws Exception
     */
    public static function getTransferringAgencyObject(array $args = []): stdClass
    {
        $TransferringAgencyObject = new stdClass();
        $TransferringAgencyObject->Identifier = new stdClass();
        $TransferringAgencyObject->Identifier->value =
            $args['TransferringAgency']['EntitiesInformations']['business_id'];

        $TransferringAgencyObject->OrganizationDescriptiveMetadata = new stdClass();

        $entityRoot = EntityModel::getEntityRootById(
            ['entityId' => $args['TransferringAgency']['EntitiesInformations']['entity_id']]
        );
        $TransferringAgencyObject->OrganizationDescriptiveMetadata->LegalClassification = $entityRoot['entity_label'];
        $TransferringAgencyObject->OrganizationDescriptiveMetadata->Name =
            $args['TransferringAgency']['EntitiesInformations']['entity_label'];
        $TransferringAgencyObject->OrganizationDescriptiveMetadata->UserIdentifier = $GLOBALS['login'];

        $traCommunicationObject = new stdClass();

        $aDefaultConfig = ReceiveMessageExchangeController::readXmlConfig();

        // If communication_type is an url, and there is a separate field for login and password,
        // we recreate the url with the login and password
        if (filter_var($aDefaultConfig['m2m_communication_type'][$args['ChannelType']], FILTER_VALIDATE_URL)) {
            if (!empty($aDefaultConfig['m2m_login']) && !empty($aDefaultConfig['m2m_password'])) {
                $prefix = '';
                if (
                    strrpos(
                        $aDefaultConfig['m2m_communication_type'][$args['ChannelType']],
                        "http://"
                    ) !== false
                ) {
                    $prefix = "http://";
                } elseif (
                    strrpos(
                        $aDefaultConfig['m2m_communication_type'][$args['ChannelType']],
                        "https://"
                    ) !==
                    false
                ) {
                    $prefix = "https://";
                }
                $url = str_replace(
                    $prefix,
                    '',
                    $aDefaultConfig['m2m_communication_type'][$args['ChannelType']]
                );
                $login = $aDefaultConfig['m2m_login'][0] ?? '';
                $password = $aDefaultConfig['m2m_password'][0] ?? '';
                $aDefaultConfig['m2m_communication_type'][$args['ChannelType']] = $prefix . $login . ':' . $password .
                    '@' . $url;
            }
        }

        $traCommunicationObject->Channel = $args['ChannelType'];
        $traCommunicationObject->value = rtrim(
            $aDefaultConfig['m2m_communication_type'][$args['ChannelType']],
            "/"
        );

        $TransferringAgencyObject->OrganizationDescriptiveMetadata->Communication = [$traCommunicationObject];

        $userInfo = UserModel::getById([
            'id'     => $GLOBALS['id'],
            'select' => ['firstname', 'lastname', 'mail', 'phone']
        ]);

        $contactUserObject = new stdClass();
        $contactUserObject->DepartmentName = $args['TransferringAgency']['EntitiesInformations']['entity_label'];
        $contactUserObject->PersonName = $userInfo['firstname'] . " " . $userInfo['lastname'];

        $communicationUserPhoneObject = new stdClass();
        $communicationUserPhoneObject->Channel = 'phone';
        $communicationUserPhoneObject->value = $userInfo['phone'];

        $communicationUserEmailObject = new stdClass();
        $communicationUserEmailObject->Channel = 'email';
        $communicationUserEmailObject->value = $userInfo['mail'];

        $contactUserObject->Communication = [$communicationUserPhoneObject, $communicationUserEmailObject];

        $TransferringAgencyObject->OrganizationDescriptiveMetadata->Contact = [$contactUserObject];

        return $TransferringAgencyObject;
    }

    /**
     * @param array $args
     * @return true
     * @throws Exception
     */
    public static function saveUnitIdentifier(array $args = []): bool
    {
        foreach ($args['attachment'] as $key => $value) {
            $disposition = "attachment";
            if ($key == 0) {
                $disposition = "body";
            }

            MessageExchangeModel::insertUnitIdentifier([
                'messageId'   => $args['messageId'],
                'tableName'   => $value['tablenameExchangeMessage'],
                'resId'       => $value['res_id'],
                'disposition' => $disposition
            ]);
        }

        if (!empty($args['notes'])) {
            foreach ($args['notes'] as $value) {
                MessageExchangeModel::insertUnitIdentifier([
                    'messageId'   => $args['messageId'],
                    'tableName'   => "notes",
                    'resId'       => $value,
                    'disposition' => "note"
                ]);
            }
        }

        return true;
    }
}
