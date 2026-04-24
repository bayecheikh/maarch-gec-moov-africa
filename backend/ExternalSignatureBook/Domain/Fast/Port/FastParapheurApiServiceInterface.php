<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Fast Parapheur Api Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Fast\Port;

use MaarchCourrier\Core\Domain\Problem\Curl\CurlRequestErrorProblem;

interface FastParapheurApiServiceInterface
{
    /**
     * Before calling this method, you must check is the previous call was 30 minutes ago.
     *
     * @param string $externalDocumentId
     *
     * @return array
     * @throws CurlRequestErrorProblem
     */
    public function getHistory(string $externalDocumentId): array;

    /**
     * @param bool $format If true, format the result to return only "idToDisplay" and "email"
     *
     * @return array
     * @throws CurlRequestErrorProblem
     */
    public function getUsers(bool $format = false): array;
}
