<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief External Signature Book Service class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Infrastructure;

use MaarchCourrier\ExternalSignatureBook\Domain\ExternalSignatureBookType;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\ExternalSignatureBookConfigServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Problem\ExternalSignatureBookConfigurationFileMissingOrEmptyProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Problem\ExternalSignatureBookFailedToGetConfigurationOfIdProblem;
use SimpleXMLElement;
use SrcCore\models\CoreConfigModel;

class ExternalSignatureBookConfigService implements ExternalSignatureBookConfigServiceInterface
{
    /**
     * @return string
     * @throws ExternalSignatureBookConfigurationFileMissingOrEmptyProblem
     */
    public function getEnable(): string
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if (empty($loadedXml)) {
            throw new ExternalSignatureBookConfigurationFileMissingOrEmptyProblem();
        }

        return isset($loadedXml->signatoryBookEnabled) ? (string)$loadedXml->signatoryBookEnabled : '';
    }

    /**
     * @throws ExternalSignatureBookConfigurationFileMissingOrEmptyProblem
     * @throws ExternalSignatureBookFailedToGetConfigurationOfIdProblem
     */
    public function getConfigById(ExternalSignatureBookType $type): SimpleXMLElement
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if (empty($loadedXml)) {
            throw new ExternalSignatureBookConfigurationFileMissingOrEmptyProblem();
        }

        $xmlElement = $loadedXml->xpath("//signatoryBook[id='$type->value']");
        if (empty($xmlElement)) {
            throw new ExternalSignatureBookFailedToGetConfigurationOfIdProblem($type->value);
        }

        return $xmlElement[0];
    }
}
