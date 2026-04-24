<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Token Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Infrastructure;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MaarchCourrier\Core\Domain\TokenServiceInterface;
use SrcCore\models\CoreConfigModel;
use stdClass;

class TokenService implements TokenServiceInterface
{
    private const ALGORITHM = 'HS256';
    private string $encryptKey;

    public function __construct()
    {
        $this->encryptKey = CoreConfigModel::getEncryptKey();
    }

    public function generate(array $payload): string
    {
        return JWT::encode($payload, $this->encryptKey, self::ALGORITHM);
    }

    /**
     * @param string $token
     *
     * @return null|stdClass
     */
    public function decode(string $token): ?stdClass
    {
        try {
            $headers = new stdClass();
            $headers->headers = [self::ALGORITHM];
            $encryptKey = $this->encryptKey;
            $key = new Key($encryptKey, self::ALGORITHM);
            return JWT::decode($token, $key, $headers);
        } catch (Exception) {
            return null;
        }
    }
}
