<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Model
 * @author dev@maarch.org
 */

namespace MessageExchange\models;

use Exception;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Resource\controllers\StoreController;
use Docserver\controllers\DocserverController;

abstract class MessageExchangeModelAbstract
{
    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function get(array $args = []): array
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit', 'offset']);

        return DatabaseModel::select([
            'select'   => empty($args['select']) ? ['*'] : $args['select'],
            'table'    => ['message_exchange'],
            'where'    => empty($args['where']) ? [] : $args['where'],
            'data'     => empty($args['data']) ? [] : $args['data'],
            'order_by' => empty($args['orderBy']) ? [] : $args['orderBy'],
            'offset'   => empty($args['offset']) ? 0 : $args['offset'],
            'limit'    => empty($args['limit']) ? 0 : $args['limit']
        ]);
    }


    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getMessageByReference(array $args = []): array
    {
        ValidatorModel::notEmpty($args, ['reference']);
        ValidatorModel::arrayType($args, ['orderBy']);

        $aReturn = DatabaseModel::select([
            'select'   => empty($args['select']) ? ['*'] : $args['select'],
            'table'    => ['message_exchange'],
            'where'    => ['reference = ?'],
            'data'     => [$args['reference']],
            'order_by' => empty($args['orderBy']) ? [] : $args['orderBy']
        ]);

        if (empty($aReturn[0])) {
            return [];
        }

        return $aReturn[0];
    }


    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getMessageByIdentifier(array $args = []): array
    {
        ValidatorModel::notEmpty($args, ['messageId']);

        $aReturn = DatabaseModel::select(
            [
                'select' => empty($args['select']) ? ['*'] : $args['select'],
                'table'  => ['message_exchange'],
                'where'  => ['message_id = ?'],
                'data'   => [$args['messageId']]
            ]
        );

        if (empty($aReturn[0])) {
            return [];
        }

        return $aReturn[0];
    }


    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function updateStatusMessage(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['status', 'messageId']);
        ValidatorModel::stringType($args, ['status', 'messageId']);

        DatabaseModel::update([
            'table' => 'message_exchange',
            'set'   => [
                'status' => $args['status']
            ],
            'where' => ['message_id = ?'],
            'data'  => [$args['messageId']]
        ]);

        return true;
    }


    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function delete(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'message_exchange',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }


    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function deleteUnitIdentifier(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'unit_identifier',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }


    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function updateOperationDateMessage(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['operation_date', 'message_id']);

        DatabaseModel::update([
            'table' => 'message_exchange',
            'set'   => [
                'operation_date' => $args['operation_date']
            ],
            'where' => ['message_id = ?'],
            'data'  => [$args['message_id']]
        ]);

        return true;
    }


    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function updateReceptionDateMessage(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['reception_date', 'message_id']);

        DatabaseModel::update([
            'table' => 'message_exchange',
            'set'   => [
                'reception_date' => $args['reception_date']
            ],
            'where' => ['message_id = ?'],
            'data'  => [$args['message_id']]
        ]);

        return true;
    }

    /*** Generates a local unique identifier
     * @return string The unique id
     */
    public static function generateUniqueId(): string
    {
        $parts = explode('.', (string)microtime(as_float: true));
        $sec = $parts[0];
        if (!isset($parts[1])) {
            $msec = 0;
        } else {
            $msec = $parts[1];
        }
        return str_pad(base_convert($sec, 10, 36), 6, '0', STR_PAD_LEFT) .
            str_pad(base_convert((string)$msec, 10, 16), 4, '0', STR_PAD_LEFT) .
            str_pad(base_convert((string)mt_rand(), 10, 36), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param array $args
     * @return array|Exception[]|string[]
     * @throws Exception
     */
    public static function insertMessage(array $args = []): array
    {
        $messageObject = $args['data'];
        $type = $args['type'];
        $argsData = $args['dataExtension'];
        $userId = $args['userId'];

        if (empty($messageObject->messageId)) {
            $messageObject->messageId = self::generateUniqueId();
        }

        if (empty($argsData['status'])) {
            $status = "sent";
        } else {
            $status = $argsData['status'];
        }

        if (empty($argsData['fullMessageObject'])) {
            $messageObjectToSave = $messageObject;
        } else {
            $messageObjectToSave = $argsData['fullMessageObject'];
        }

        if (empty($argsData['resIdMaster'])) {
            $resIdMaster = null;
        } else {
            $resIdMaster = $argsData['resIdMaster'];
        }

        if (empty($argsData['filePath'])) {
            $filePath = null;
        } else {
            $filePath = $argsData['filePath'];
            $filesize = filesize($filePath);

            //Store resource on docserver
            $resource = file_get_contents($filePath);
            $pathInfo = pathinfo($filePath);
            $storeResult = DocserverController::storeResourceOnDocServer([
                'collId'          => 'archive_transfer_coll',
                'docserverTypeId' => 'ARCHIVETRANSFER',
                'encodedResource' => base64_encode($resource),
                'format'          => $pathInfo['extension']
            ]);

            if (!empty($storeResult['errors'])) {
                return ['error' => $storeResult['errors']];
            }
            $docserverId = $storeResult['docserver_id'];
            $filepath = $storeResult['destination_dir'];
            $filename = $storeResult['file_destination_name'];
            $docserver = DocserverModel::getByDocserverId(['docserverId' => $docserverId]);

            $docserverType = DocserverTypeModel::getById([
                'id' => $docserver['docserver_type_id']
            ]);

            $fingerprint = StoreController::getFingerPrint([
                'filePath' => $filePath,
                'mode'     => $docserverType['fingerprint_mode'],
            ]);
        }

        try {
            DatabaseModel::insert([
                'table'         => 'message_exchange',
                'columnsValues' => [
                    'message_id'                   => $messageObject->messageId,
                    'schema'                       => "2.1",
                    'type'                         => $type,
                    'status'                       => $status,
                    'date'                         => $messageObject->date,
                    'reference'                    => $messageObject->MessageIdentifier->value,
                    'account_id'                   => $userId,
                    'sender_org_identifier'        => $messageObject->TransferringAgency->Identifier->value,
                    'sender_org_name'              => $argsData['SenderOrgNAme'],
                    'recipient_org_identifier'     => $messageObject->ArchivalAgency->Identifier->value,
                    'recipient_org_name'           => $argsData['RecipientOrgNAme'],
                    'archival_agreement_reference' => $messageObject->ArchivalAgreement->value,
                    'reply_code'                   => $messageObject->ReplyCode ?? null,
                    'size'                         => '0',
                    'data'                         => json_encode($messageObjectToSave),
                    'active'                       => 'true',
                    'archived'                     => 'false',
                    'res_id_master'                => $resIdMaster,
                    'docserver_id'                 => $docserverId ?? null,
                    'path'                         => $filepath ?? null,
                    'filename'                     => $filename ?? null,
                    'fingerprint'                  => $fingerprint ?? null,
                    'filesize'                     => $filesize ?? null
                ]
            ]);
        } catch (Exception $e) {
            return ['error' => $e];
        }

        return ['messageId' => $messageObject->messageId];
    }


    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getUnitIdentifierByMessageId(array $args): array
    {
        ValidatorModel::notEmpty($args, ['messageId']);
        ValidatorModel::stringType($args, ['messageId']);

        $messages = DatabaseModel::select([
            'select' => empty($args['select']) ? ['*'] : $args['select'],
            'table'  => ['unit_identifier'],
            'where'  => ['message_id = ?'],
            'data'   => [$args['messageId']]
        ]);

        return $messages;
    }


    /**
     * @param array $args
     * @return array
     * @throws Exception
     */
    public static function getUnitIdentifierByResId(array $args): array
    {
        ValidatorModel::notEmpty($args, ['resId']);

        $messages = DatabaseModel::select([
            'select' => empty($args['select']) ? ['*'] : $args['select'],
            'table'  => ['unit_identifier'],
            'where'  => ['res_id = ?'],
            'data'   => [$args['resId']]
        ]);

        return $messages;
    }


    /**
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function insertUnitIdentifier(array $args): bool
    {
        ValidatorModel::notEmpty($args, ['messageId', 'tableName', 'resId']);
        ValidatorModel::stringType($args, ['messageId', 'tableName', 'disposition']);
        ValidatorModel::intVal($args, ['resId']);

        $messages = DatabaseModel::insert([
            'table'         => 'unit_identifier',
            'columnsValues' => [
                'message_id'  => $args['messageId'],
                'tablename'   => $args['tableName'],
                'res_id'      => $args['resId'],
                'disposition' => $args['disposition'] ?? null
            ]
        ]);

        return $messages;
    }
}
