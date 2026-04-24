<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Main Resource Repository Interface
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Core\Domain\MainResource\Port;

use MaarchCourrier\DocumentStorage\Domain\Document;

interface MainResourceRepositoryInterface
{
    public function doesExistOnView(MainResourceInterface $mainResource, string $basketClause): bool;

    public function getMainResourceByResId(int $resId): ?MainResourceInterface;

    public function getMainResourcesByResIds(array $resIds): array;

    public function isMainResourceSigned(MainResourceInterface $mainResource): bool;

    public function removeSignatureBookLink(MainResourceInterface $mainResource): void;

    public function getLastNotAnnotatedResourceVersionByResId(int $resId): ?int;

    public function getDocumentByResIdAndVersion(int $resId, int $version): ?Document;

    /**
     * @return MainResourceInterface[]
     */
    public function getOnViewByClause(array $where, array $data): array;

    public function updateMainResourceStatus(MainResourceInterface $mainResource, string $status): void;
}
