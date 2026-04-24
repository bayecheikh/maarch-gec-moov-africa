<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Lang Service Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Port;

interface LangServiceInterface
{
    public function getLanguageType(): string;

    public function getLanguage(): array;
}
