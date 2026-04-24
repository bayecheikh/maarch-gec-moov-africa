<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Api Token Notification Service class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\User\Infrastructure;

use Exception;
use History\controllers\HistoryController;
use MaarchCourrier\User\Domain\Port\ApiTokenScheduleServiceInterface;
use Notification\models\NotificationScheduleModel;
use SrcCore\models\CoreConfigModel;

class ApiTokenScheduleService implements ApiTokenScheduleServiceInterface
{
    /** Unique tag to identify this specific cron entry (helps safe deletion). */
    private const CRON_TAG = '# MaarchCourrier:ApiTokenExpirationNotificationAlertScript';
    private const ALERT_SCRIPT_NAME = 'ApiTokenExpirationNotificationAlertScript.php';

    /**
     * Persist a crontab state (create or delete) through the NotificationScheduleModel.
     * @throws Exception
     */
    private function crontabAction(array $crontabValues): void
    {
        // save the crontab state (normal or delete) that will create the events
        NotificationScheduleModel::saveCrontab(['crontab' => [$crontabValues]]);
    }

    /**
     * Build the cron entry payload understood by NotificationScheduleModel.
     *
     * @param 'normal'|'deleted' $state
     * @param string $scriptPath
     *
     * @return array
     */
    private function buildCronPayload(string $state, string $scriptPath): array
    {
        // Always escape shell args to be safe with spaces/special chars.
        $scriptPath = escapeshellarg($scriptPath);

        // Tag the command so deletion can be scoped safely by the underlying model if supported.
        $cmd = "sh $scriptPath " . self::CRON_TAG;

        return [
            'state' => $state, // "normal" to create, "deleted" to remove
            // Every day at 00:00 server time (cron semantics).
            'm'     => '0',
            'h'     => '0',
            'dom'   => '*',
            'mon'   => '*',
            'dow'   => '*',
            'cmd'   => $cmd,
        ];
    }

    /**
     * Create (or ensure) the daily 00:00 notification alert cron
     *
     * @return void
     * @throws Exception
     */
    public function createNotifAlert(): void
    {
        $configPath = CoreConfigModel::getConfigPath();
        $config = CoreConfigModel::getJsonLoaded(['path' => $configPath]);
        $rootDirectory = rtrim($config['config']['maarchDirectory'], '/');
        $configPath = "$rootDirectory/bin/notification/$configPath";

        $scriptPhpFilePathFromRootDir = "bin/notification/" . self::ALERT_SCRIPT_NAME;
        $scriptShFilePath = self::getCronScriptPath($rootDirectory);
        $fileOpen = fopen($scriptShFilePath, 'w+');

        fwrite($fileOpen, '#!/bin/sh');
        fwrite($fileOpen, "\n");
        fwrite($fileOpen, "cd $rootDirectory");
        fwrite($fileOpen, "\n");
        fwrite($fileOpen, "php $scriptPhpFilePathFromRootDir -c $configPath ");
        fwrite($fileOpen, "\n");
        fclose($fileOpen);
        shell_exec('chmod +x ' . escapeshellarg($scriptShFilePath));

        $this->crontabAction($this->buildCronPayload('normal', $scriptShFilePath));

        HistoryController::add([
            'tableName' => 'notifications',
            'recordId'  => self::CRON_TAG,
            'eventType' => 'ADD',
            'eventId'   => 'notificationadd',
            'info'      => _NOTIFICATION_SCRIPT_ADDED,
        ]);
    }

    public function doesNotifAlert(): bool
    {
        $crontab = NotificationScheduleModel::getCrontab(['setHiddenValue' => false]);
        $crontabScriptInfo = array_filter(
            $crontab,
            fn(array $value) => isset($value['state'])
                && $value['state'] == 'normal'
                && str_contains($value['cmd'], self::CRON_TAG)
        );
        return !empty($crontabScriptInfo);
    }

    /**
     * Remove the notification alert cron
     *
     * @return void
     * @throws Exception
     */
    public function deleteNotifAlert(): void
    {
        $configPath = CoreConfigModel::getConfigPath();
        $config = CoreConfigModel::getJsonLoaded(['path' => $configPath]);
        $rootDirectory = rtrim($config['config']['maarchDirectory'], '/');
        $scriptShFilePath = self::getCronScriptPath($rootDirectory);
        $this->crontabAction($this->buildCronPayload('deleted', $scriptShFilePath));
        shell_exec("rm $scriptShFilePath");
    }

    private function getCronScriptPath(string $rootDirectory): string
    {
        $alertScriptShFilename = 'notification';
        $customId = CoreConfigModel::getCustomId();
        if (!empty($customId)) {
            $alertScriptShFilename .= '_' . str_replace(' ', '', $customId);
        }
        $alertScriptShFilename .= '_ApiTokenExpirationAlert.sh';

        $pathToFollow = $rootDirectory;
        if (!empty($customId)) {
            $pathToFollow = "$rootDirectory/custom/$customId";
            if (!file_exists("$pathToFollow/bin/notification/scripts/")) {
                mkdir("$pathToFollow/bin/notification/scripts/", 0777, true);
            }
        }

        return "$pathToFollow/bin/notification/scripts/$alertScriptShFilename";
    }
}
