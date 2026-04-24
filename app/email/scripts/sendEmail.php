<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Email Script
 * @author dev@maarch.org
 */

namespace Email\scripts;

// phpcs:disable
require 'vendor/autoload.php';
// phpcs:enable

use AcknowledgementReceipt\models\AcknowledgementReceiptModel;
use Exception;
use MaarchCourrier\Core\Infrastructure\Configuration\ConfigurationRepository;
use MaarchCourrier\Email\Domain\AdminEmailServerPrivilege;
use MaarchCourrier\Email\Infrastructure\Repository\EmailRepository;
use MaarchCourrier\Email\Infrastructure\SendEmailFactory;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use SrcCore\controllers\LogsController;
use SrcCore\models\DatabasePDO;
use User\models\UserModel;

//customId   = $argv[1];
//emailId    = $argv[2];
//userId     = $argv[3];
//encryptKey = $argv[4];
//options    = $argv[5];

// phpcs:disable
$options = empty($argv[5]) ? null : unserialize($argv[5]);

EmailScript::send([
    'customId'   => $argv[1],
    'emailId'    => $argv[2],
    'userId'     => $argv[3],
    'encryptKey' => $argv[4],
    'options'    => $options
]);
// phpcs:enable

class EmailScript
{
    /**
     * @param array $args
     *
     * @throws Exception
     */
    public static function send(array $args): void
    {
        $GLOBALS['customId'] = $args['customId'];

        DatabasePDO::reset();
        new DatabasePDO(['customId' => $args['customId']]);

        $currentUser = UserModel::getById(['id' => $args['userId'], 'select' => ['user_id']]);
        $GLOBALS['login'] = $currentUser['user_id'];
        $GLOBALS['id'] = $args['userId'];
        $GLOBALS['customId'] = $args['customId'];
        $_SERVER['MAARCH_ENCRYPT_KEY'] = $args['encryptKey'];

        $emailRepository = new EmailRepository(new UserRepository());
        $email = $emailRepository->getById($args['emailId']);

        $logConfig = LogsController::getLogConfig();
        $logTypeInfo = LogsController::getLogType('logTechnique');
        $logger = LogsController::initMonologLogger(
            $logConfig,
            $logTypeInfo,
            false,
            $args['customId']
        );

        $serverEmailConfig = (new ConfigurationRepository())->getByPrivilege(new AdminEmailServerPrivilege());
        $sendEmail = SendEmailFactory::create($logger);
        $sendEmail->setEmailServerConfig($serverEmailConfig);
        $didEmailSent = $sendEmail->execute($email);

        if ($didEmailSent) {
            $emailRepository->updateEmail($email, ['status' => 'SENT', 'send_date' => 'CURRENT_TIMESTAMP']);
        } else {
            $emailRepository->updateEmail($email, ['status' => 'ERROR']);
        }

        //Options
        if (!empty($args['options']['acknowledgementReceiptId']) && $didEmailSent) {
            AcknowledgementReceiptModel::update(
                [
                    'set'   => ['send_date' => 'CURRENT_TIMESTAMP'],
                    'where' => ['id = ?'],
                    'data'  => [$args['options']['acknowledgementReceiptId']]
                ]
            );
        }
    }
}
