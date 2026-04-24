<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Get Correspondents class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;

class GoodflagGetCorrespondents
{
    public function __construct(
        private readonly GoodflagApiServiceInterface $goodflagApiService
    ) {
    }

    /**
     * @param string|null $search
     * @return array
     * @throws GoodflagConfigNotFoundProblem
     */
    public function execute(?string $search = null): array
    {
        $this->goodflagApiService->loadConfig();

        $users = $this->goodflagApiService->retrieveUsers($search);
        $contacts = $this->goodflagApiService->retrieveContacts($search);

        $arrayMerged = array_merge($users, $contacts);
        $result = [];

        foreach ($arrayMerged as $item) {
            $result[] = [
                'type'        => str_starts_with($item['id'], 'usr') ? 'user' : 'contact',
                'id'          => $item['id'],
                'firstname'   => $item['firstName'],
                'lastname'    => $item['lastName'],
                'idToDisplay' => $item['name'],
                'email'       => $item['email'],
                'phoneNumber' => $item['phoneNumber'],
                'country'     => $item['country']
            ];
        }
        return $result;
    }
}
