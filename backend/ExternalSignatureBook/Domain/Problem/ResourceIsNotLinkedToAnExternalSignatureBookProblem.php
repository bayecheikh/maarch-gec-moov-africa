<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Resource Is Not Linked To An External Signature Book Problem class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\Problem\Problem;

class ResourceIsNotLinkedToAnExternalSignatureBookProblem extends Problem
{
    public function __construct(
        MainResourceInterface|AttachmentInterface $resource,
        string $esbName
    ) {
        $identifier = ($resource instanceof MainResourceInterface ? 'MainResource|' : 'Attachment|') .
            ($resource->getChrono() ?? $resource->getResId());
        parent::__construct(
            "Resource '$identifier' is not linked to an external signature book: '$esbName'",
            400,
            [
                'externalSignatureBookName' => $esbName,
                'resource'                  => [
                    'type'   => ($resource instanceof MainResourceInterface ? 'MainResource' : 'Attachment'),
                    'id'     => $resource->getResId(),
                    'chrono' => $resource->getChrono()
                ]
            ],
            'resourceIsNotLinkedToAnExternalSignatureBook'
        );
    }
}
