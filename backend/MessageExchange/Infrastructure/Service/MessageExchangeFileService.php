<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Message Exchange File Service class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\MessageExchange\Infrastructure\Service;

use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Exception;
use MaarchCourrier\Core\Domain\DocumentStorage\Problem\DocumentNotFoundOnDocserverProblem;
use MaarchCourrier\Core\Domain\MessageExchange\Port\MessageExchangeFileServiceInterface;
use MaarchCourrier\Core\Domain\MessageExchange\Problem\CouldNotGetMessageExchangeFileProblem;
use MessageExchange\models\MessageExchangeModel;
use Resource\controllers\StoreController;
use SrcCore\models\TextFormatModel;

class MessageExchangeFileService implements MessageExchangeFileServiceInterface
{
    /**
     * @param int $id
     *
     * @return array An empty array if no file was found, or an array that contains:
     *        - 'fileContent': File content.
     *        - 'filename': File name.
     * @throws CouldNotGetMessageExchangeFileProblem
     * @throws Exception
     */
    public function getFileNameAndContentById(int $id): array
    {
        $messageExchange = MessageExchangeModel::getMessageByIdentifier([
            'messageId' => $id,
            'select'    => ['docserver_id', 'path', 'filename', 'fingerprint', 'reference']
        ]);
        $docserver = DocserverModel::getByDocserverId(['docserverId' => $messageExchange['docserver_id']]);
        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id']]);

        $pathDirectory = str_replace('#', DIRECTORY_SEPARATOR, $messageExchange['path']);
        $filePath = $docserver['path_template'] . $pathDirectory . $messageExchange['filename'];
        $fingerprint = StoreController::getFingerPrint([
            'filePath' => $filePath,
            'mode'     => $docserverType['fingerprint_mode'],
        ]);

        if ($fingerprint != $messageExchange['fingerprint']) {
            throw new CouldNotGetMessageExchangeFileProblem('Fingerprint document does not match');
        }

        if (is_file($filePath)) {
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                throw new DocumentNotFoundOnDocserverProblem();
            }

            $title = TextFormatModel::formatFilename([
                'filename'  => $messageExchange['reference'],
                'maxLength' => 30
            ]);

            return ['fileContent' => $fileContent, 'filename' => "$title.zip"];
        }

        return [];
    }
}
