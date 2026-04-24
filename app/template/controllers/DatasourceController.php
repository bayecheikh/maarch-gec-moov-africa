<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Datasource Controller
 * @author dev@maarch.org
 */

namespace Template\controllers;

use Basket\models\BasketModel;
use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Contact\models\ContactParameterModel;
use CustomField\models\CustomFieldModel;
use Entity\models\EntityModel;
use Exception;
use Group\controllers\PrivilegeController;
use Note\models\NoteModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\TextFormatModel;
use User\models\UserBasketPreferenceModel;
use User\models\UserModel;

class DatasourceController
{
    /**
     * @param array $aArgs
     *
     * @return array
     */
    public static function notifEvents(array $aArgs): array
    {
        $dataSources['notification'][0] = $aArgs['params']['notification'];
        $dataSources['recipient'][0] = $aArgs['params']['recipient'];
        $dataSources['events'] = [];

        foreach ($aArgs['params']['events'] as $event) {
            $dataSources['events'][] = $event;
        }

        return $dataSources;
    }

    /**
     * @param array $aArgs
     *
     * @return array
     * @throws Exception
     */
    public static function letterboxEvents(array $aArgs): array
    {
        $dataSources['recipient'][0] = $aArgs['params']['recipient'];
        $dataSources['res_letterbox'] = [];
        $dataSources['sender'] = [];

        $basket = BasketModel::getByBasketId(['select' => ['id'], 'basketId' => 'MyBasket']);
        $preferenceBasket = UserBasketPreferenceModel::get([
            'select' => ['group_serial_id'],
            'where'  => ['user_serial_id = ?', 'basket_id = ?'],
            'data'   => [$aArgs['params']['recipient']['id'], 'MyBasket']
        ]);

        foreach ($aArgs['params']['events'] as $event) {
            $table = [$aArgs['params']['res_view'] . ' lb'];
            $leftJoin = [];
            $where = [];
            $arrayPDO = [];

            switch ($event['table_name']) {
                case 'notes':
                    $table[] = 'notes';
                    $leftJoin[] = 'notes.identifier = lb.res_id';
                    $where[] = 'notes.id = ?';
                    $arrayPDO[] = $event['record_id'];
                    break;

                case 'listinstance':
                    $table[] = 'listinstance li';
                    $leftJoin[] = 'lb.res_id = li.res_id';
                    $where[] = 'listinstance_id = ?';
                    $arrayPDO[] = $event['record_id'];
                    break;

                case 'res_letterbox':
                case 'res_view_letterbox':
                default:
                    $where[] = 'lb.res_id = ?';
                    $arrayPDO[] = $event['record_id'];
            }

            // Main document resource from view
            $res = DatabaseModel::select([
                'select'    => ['lb.*'],
                'table'     => $table,
                'left_join' => $leftJoin,
                'where'     => $where,
                'data'      => $arrayPDO,
            ])[0];

            // Lien vers la page detail
            $res['linktodoc'] = trim($aArgs['params']['maarchUrl'], '/') . '/dist/index.html#/resources/' .
                $res['res_id'] . '/content';
            $res['linktodetail'] = trim($aArgs['params']['maarchUrl'], '/') . '/dist/index.html#/resources/' .
                $res['res_id'];
            if (
                !empty($res['res_id']) && !empty($preferenceBasket[0]['group_serial_id']) && !empty($basket['id']) &&
                !empty($aArgs['params']['recipient']['id'])
            ) {
                $res['linktoprocess'] = trim($aArgs['params']['maarchUrl'], '/') .
                    '/dist/index.html#/process/users/' . $aArgs['params']['recipient']['id'] . '/groups/' .
                    $preferenceBasket[0]['group_serial_id'] . '/baskets/' . $basket['id'] . '/resId/' . $res['res_id'];
            }

            if (!empty($res['initiator'])) {
                $entityInfo = EntityModel::getByEntityId(['select' => ['*'], 'entityId' => $res['initiator']]);
                foreach ($entityInfo as $key => $value) {
                    $res['initiator_' . $key] = $value;
                }
            }

            if (!empty($res['typist'])) {
                $userInfo = UserModel::getById(['select' => ['firstname', 'lastname'], 'id' => $res['typist']]);
                $res['typist_label'] = $userInfo['firstname'] . ' ' . $userInfo['lastname'];
            }

            $res['basketName'] = '';
            if (!empty($event['basketName'])) {
                $res['basketName'] = $event['basketName'];
            }

            // CustomFields
            $resCustomFieldsData = DatasourceController::getCustomFieldsData([
                'custom_fields' => $res['custom_fields']
            ]);
            $res = array_merge($res, $resCustomFieldsData);

            $dataSources['res_letterbox'][] = $res;

            $resourceContacts = ResourceContactModel::get([
                'where' => ['res_id = ?', "mode='sender'", "type='contact'"],
                'data'  => [$res['res_id']],
                'limit' => 1
            ]);
            $resourceContacts = $resourceContacts[0] ?? null;

            $contact = [];
            if (!empty($resourceContacts)) {
                $contact = ContactModel::getById(['id' => $resourceContacts['item_id'], 'select' => ['*']]);
                $contact = self::contactDatasWithConfidentiality($aArgs['params']['recipient']['id'], $contact);

                $postalAddress = ContactController::getContactAfnor($contact);
                unset($postalAddress[0]);
                $contact['postal_address'] = implode("\n", $postalAddress);
            }
            $dataSources['sender'][] = $contact;
        }

        return $dataSources;
    }

    /**
     * @param array $aArgs
     *
     * @return array
     * @throws Exception
     */
    public static function noteEvents(array $aArgs): array
    {
        $dataSources['recipient'][0] = $aArgs['params']['recipient'];
        $dataSources['notes'] = [];

        $basket = BasketModel::getByBasketId(['select' => ['id'], 'basketId' => 'MyBasket']);
        $preferenceBasket = UserBasketPreferenceModel::get([
            'select' => ['group_serial_id'],
            'where'  => ['user_serial_id = ?', 'basket_id = ?'],
            'data'   => [$aArgs['params']['recipient']['id'], 'MyBasket']
        ]);

        foreach ($aArgs['params']['events'] as $event) {
            if ($event['table_name'] != 'notes') {
                $note = DatabaseModel::select([
                    'select'    => ['mlb.*', 'notes.*', 'users.*'],
                    'table'     => ['listinstance li', $aArgs['params']['res_view'] . ' mlb', 'notes', 'users'],
                    'left_join' => [
                        'mlb.res_id = li.res_id',
                        'notes.identifier = li.res_id',
                        'users.id = notes.user_id'
                    ],
                    'where'     => [
                        'li.item_id = ?',
                        'li.item_mode = \'dest\'',
                        'li.item_type = \'user_id\'',
                        'li.res_id = ?'
                    ],
                    'data'      => [$aArgs['params']['recipient']['id'], $event['record_id']],
                ]);
                $note = !empty($note[0]) ? $note[0] : [];
                $resId = $note['identifier'] ?? null;
            } else {
                $note = NoteModel::getById(['id' => $event['record_id']]);
                $resId = $note['identifier'];
                $resLetterbox = ResModel::getById(['select' => ['*'], 'resId' => $resId]);
                $dataSources['res_letterbox'][] = $resLetterbox;
            }

            $note['linktodoc'] = trim($aArgs['params']['maarchUrl'], '/') .
                '/dist/index.html#/resources/' . $resId . '/content';
            $note['linktodetail'] = trim($aArgs['params']['maarchUrl'], '/') .
                '/dist/index.html#/resources/' . $resId;

            if (
                !empty($resId) && !empty($preferenceBasket[0]['group_serial_id']) && !empty($basket['id']) &&
                !empty($aArgs['params']['recipient']['id'])
            ) {
                $note['linktoprocess'] = trim($aArgs['params']['maarchUrl'], '/') .
                    '/dist/index.html#/process/users/' . $aArgs['params']['recipient']['id'] . '/groups/' .
                    $preferenceBasket[0]['group_serial_id'] . '/baskets/' . $basket['id'] . '/resId/' . $resId;
            }

            if (!empty($resId)) {
                $resourceContacts = ResourceContactModel::get([
                    'where' => ['res_id = ?', "type = 'contact'", "mode = 'sender'"],
                    'data'  => [$resId],
                    'limit' => 1
                ]);
                $resourceContacts = $resourceContacts[0];
            }

            if ($event['table_name'] == 'notes') {
                $dataSources['res_letterbox'][array_key_last(
                    $dataSources['res_letterbox']
                )]['linktodoc'] = $note['linktodoc'];
                $dataSources['res_letterbox'][array_key_last(
                    $dataSources['res_letterbox']
                )]['linktodetail'] = $note['linktodetail'];
                $dataSources['res_letterbox'][array_key_last(
                    $dataSources['res_letterbox']
                )]['linktoprocess'] = $note['linktodoc'];

                $labelledUser = UserModel::getLabelledUserById(['id' => $note['user_id']]);
                $creationDate = TextFormatModel::formatDate($note['creation_date'], 'd/m/Y');
                $note = "{$labelledUser}  {$creationDate} : {$note['note_text']}\n";
            }

            $contact = [];
            if (!empty($resourceContacts)) {
                $contact = ContactModel::getById(['id' => $resourceContacts['item_id'], 'select' => ['*']]);
                $contact = self::contactDatasWithConfidentiality($aArgs['params']['recipient']['id'], $contact);
            }
            $dataSources['sender'][] = $contact;

            $dataSources['notes'][] = ['content' => $note];

            // CustomFields
            $resCustomFieldsData = DatasourceController::getCustomFieldsData([
                'custom_fields' => $dataSources['res_letterbox'][0]['custom_fields']
            ]);
            $dataSources['res_letterbox'][0] = array_merge(
                $dataSources['res_letterbox'][0] ?? [],
                $resCustomFieldsData
            );
        }

        return $dataSources;
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws Exception
     */
    public static function getCustomFieldsData(array $args): array
    {
        $customFieldsData = [];
        $resCustomFields = !empty($args['custom_fields']) ? json_decode($args['custom_fields'], true) : [];
        $resCustomFieldsIds = array_keys($resCustomFields);

        if (!empty($resCustomFieldsIds)) {
            $customFields = CustomFieldModel::get([
                'select' => ['id', 'values', 'type'],
                'where'  => ['id in (?)'],
                'data'   => [$resCustomFieldsIds]
            ]);

            $customFieldsTypes = array_column($customFields, 'type', 'id');

            foreach ($resCustomFields as $customId => $customField) {
                if (is_array($customField)) {
                    if ($customFieldsTypes[$customId] == 'banAutocomplete') {
                        $customFieldsData['customField_' .
                        $customId] = "{$customField[0]['addressNumber']} {$customField[0]['addressStreet']}" .
                            " {$customField[0]['addressTown']} ({$customField[0]['addressPostcode']})";
                    } elseif ($customFieldsTypes[$customId] == 'contact') {
                        $customValues = ContactController::getContactCustomField(['contacts' => $customField]);
                        $customFieldsData['customField_' . $customId] = implode("\n", $customValues);
                    } else {
                        $customFieldsData['customField_' . $customId] = implode("\n", $customField);
                    }
                } else {
                    $customFieldsData['customField_' . $customId] = $customField;
                }
            }
        }

        return $customFieldsData;
    }

    /**
     * @param int $idRecipient
     * @param array $contactDatas
     * @return array
     * @throws Exception
     */
    private static function contactDatasWithConfidentiality(int $idRecipient, array $contactDatas): array
    {
        $canViewConfidentialContacts = PrivilegeController::hasPrivilege([
            'privilegeId' => 'view_confidential_contact_information',
            'userId'      => $idRecipient
        ]);

        if (!$canViewConfidentialContacts) {
            $confidentialFieldsContact = ContactParameterModel::get([
                'select' => ['identifier'],
                'where'  => ['confidential = ?'],
                'data'   => [true]
            ]);
            $confidentialFieldsContact = array_column($confidentialFieldsContact, 'identifier');

            if ($contactDatas['is_confidential']) {
                foreach ($confidentialFieldsContact as $confidentialField) {
                    $dbField = ContactController::MAPPING_FIELDS[$confidentialField] ?? null;
                    if (!empty($dbField) && isset($contactDatas[$dbField])) {
                        $contactDatas[$dbField] = null;
                    }
                }
            }
        }

        return $contactDatas;
    }
}
