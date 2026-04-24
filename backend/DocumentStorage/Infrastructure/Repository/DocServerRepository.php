<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief DocServer Repository
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentStorage\Infrastructure\Repository;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\DocumentStorage\Domain\DocServer;
use MaarchCourrier\DocumentStorage\Domain\Port\DocServerRepositoryInterface;
use MaarchCourrier\DocumentStorage\Domain\Port\DocServerTypeRepositoryInterface;
use MaarchCourrier\DocumentStorage\Domain\Problem\DocServerTypeDoesNotExistProblem;
use SrcCore\models\DatabaseModel;

class DocServerRepository implements DocServerRepositoryInterface
{
    public function __construct(private readonly DocServerTypeRepositoryInterface $docServerTypeRepository)
    {
    }

    /**
     * @param string $docServerId
     *
     * @return DocServer|null
     * @throws DocServerTypeDoesNotExistProblem
     * @throws Exception
     */
    public function getByStorageZoneId(string $docServerId): ?DocServer
    {
        $docServer = DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['docservers'],
            'where'     => ['docserver_id = ?'],
            'data'      => [$docServerId]
        ]);

        if (empty($docServer[0])) {
            return null;
        }
        $docServer = $docServer[0];

        $docServerType = $this->docServerTypeRepository->getById($docServer['docserver_type_id']);
        if ($docServerType == null) {
            throw new DocServerTypeDoesNotExistProblem($docServer['docserver_type_id']);
        }

        return (new DocServer())
            ->setId($docServer['id'])
            ->setDocserverId($docServer['docserver_id'])
            ->setDocserverType($docServerType)
            ->setDeviceLabel($docServer['device_label'] ?? null)
            ->setIsReadonly($docServer['is_readonly'])
            ->setSizeLimitNumber($docServer['size_limit_number'])
            ->setActualSizeNumber($docServer['actual_size_number'])
            ->setPathTemplate($docServer['path_template'])
            ->setCreationDate(new DateTimeImmutable($docServer['creation_date']))
            ->setCollId($docServer['coll_id'])
            ->setIsEncrypted($docServer['is_encrypted']);
    }
}
