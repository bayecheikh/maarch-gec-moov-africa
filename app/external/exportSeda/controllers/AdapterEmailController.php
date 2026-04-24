<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Adapter Email Controller
 * @author dev@maarch.org
 */

namespace ExportSeda\controllers;

use Configuration\models\ConfigurationModel;
use Email\controllers\EmailController;
use Exception;
use MessageExchange\models\MessageExchangeModel;
use stdClass;
use User\models\UserModel;

class AdapterEmailController
{
    /**
     * @throws Exception
     */
    public function send(stdClass $messageObject, string $messageId): array
    {
        $res['status'] = 0;
        $res['content'] = '';

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_export_seda']);
        $configuration = !empty($configuration['value']) ? json_decode($configuration['value'], true) : [];
        $gec = strtolower($configuration['M2M']['gec'] ?? '');

        $id = $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->OriginatingSystemId;
        if ($gec == 'maarch_courrier') {
            $document = [
                'id'       => $id,
                'isLinked' => false,
                'original' => false
            ];
            $userInfo = UserModel::getByLogin([
                'login'  => $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->UserIdentifier,
                'select' => ['id', 'mail']
            ]);

            $communicationValue = $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->Contact[0]
                ->Communication[1]->value;
            if (!empty($communicationValue)) {
                $senderEmail = $communicationValue;
            } else {
                $senderEmail = $userInfo['mail'];
            }

            $recipients = $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value;
            $object = $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->Title[0];
            EmailController::createEmail([
                'userId' => $userInfo['id'],
                'data'   => [
                    'sender'            => ['email' => $senderEmail],
                    'recipients'        => [$recipients],
                    'cc'                => '',
                    'cci'               => '',
                    'object'            => $object,
                    'body'              => $messageObject->Comment[0]->value,
                    'document'          => $document,
                    'isHtml'            => true,
                    'status'            => 'TO_SEND',
                    'messageExchangeId' => $messageId
                ]
            ]);

            MessageExchangeModel::updateStatusMessage(['messageId' => $messageId, 'status' => 'I']);
        }

        return $res;
    }
}
