<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Check Document Signature Fields Interface
 * @author dev@maarch.org
 * @ingroup core
 */

namespace SrcCore\interfaces;

interface CheckDocumentSignatureFieldsInterface
{
    public function checkDocumentSignatureFields(string $args): bool;
}
