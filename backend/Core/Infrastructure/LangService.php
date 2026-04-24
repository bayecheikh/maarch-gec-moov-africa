<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Lang Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Infrastructure;

use Exception;
use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\Port\LangServiceInterface;
use SrcCore\controllers\LanguageController;

class LangService implements LangServiceInterface
{
    public function __construct(
        private readonly EnvironmentInterface $environment
    ) {
    }

    /**
     * @throws Exception
     */
    public function getLanguage(): array
    {
        $lang = $this->environment->getLanguageType();
        return LanguageController::getLanguage(['language' => $lang]);
    }

    /**
     * @return string
     */
    public function getLanguageType(): string
    {
        return $this->environment->getLanguageType();
    }
}
