<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Logs Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Port;

use Monolog\Logger;

interface LogsInterface
{
    public static function initMonologLogger(
        array $logConfig,
        array $loggerConfig,
        bool $isCustomFormatLineUse,
        ?string $name
    ): Logger|array;

    public static function getLogType(string $logType): array;

    public static function getLogConfig(): ?array;

    public static function add(array $args): bool|array;

    public static function rotateLogFileBySize(array $file): void;

    public static function calculateFileSizeToBytes(string $value): ?int;
}
