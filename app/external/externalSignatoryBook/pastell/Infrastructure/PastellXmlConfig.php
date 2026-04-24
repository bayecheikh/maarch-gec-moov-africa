<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Pastell XML Config
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\pastell\Infrastructure;

use Exception;
use ExternalSignatoryBook\pastell\Domain\PastellConfig;
use ExternalSignatoryBook\pastell\Domain\PastellConfigInterface;
use ExternalSignatoryBook\pastell\Domain\PastellStates;
use SrcCore\models\CoreConfigModel;

class PastellXmlConfig implements PastellConfigInterface
{
    /**
     * @return PastellConfig|null
     * @throws Exception
     */
    public function getPastellConfig(): ?PastellConfig
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        $pastellConfig = null;
        if (!empty($loadedXml)) {
            $PastellConfig = $loadedXml->xpath('//signatoryBook[id=\'pastell\']')[0] ?? null;
            if ($PastellConfig) {
                $pastellConfig = new PastellConfig(
                    isset($PastellConfig->url) ? (string)$PastellConfig->url : null,
                    isset($PastellConfig->login) ? (string)$PastellConfig->login : null,
                    isset($PastellConfig->password) ? (string)$PastellConfig->password : null,
                    isset($PastellConfig->entityId) ? (int)$PastellConfig->entityId : null,
                    isset($PastellConfig->connectorId) ? (int)$PastellConfig->connectorId : null,
                    isset($PastellConfig->documentType) ? (string)$PastellConfig->documentType : null,
                    isset($PastellConfig->iParapheurType) ? (string)$PastellConfig->iParapheurType : null,
                    isset($PastellConfig->iParapheurSousType) ? (string)$PastellConfig->iParapheurSousType : null,
                    isset($PastellConfig->postAction) ? (string)$PastellConfig->postAction : null
                );
            }
        }
        return $pastellConfig;
    }

    /**
     * @return PastellStates|null
     * @throws Exception
     */
    public function getPastellStates(): ?PastellStates
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        $pastellState = null;
        if (!empty($loadedXml)) {
            $pastellState = $loadedXml->xpath('//signatoryBook[id=\'pastell\']')[0] ?? null;
            if ($pastellState) {
                $pastellState = new PastellStates(
                    isset($pastellState->errorCode) ? (string)$pastellState->errorCode : null,
                    isset($pastellState->visaState) ? (string)$pastellState->visaState : null,
                    isset($pastellState->signState) ? (string)$pastellState->signState : null,
                    isset($pastellState->refusedVisa) ? (string)$pastellState->refusedVisa : null,
                    isset($pastellState->refusedSign) ? (string)$pastellState->refusedSign : null
                );
            }
        }
        return $pastellState;
    }
}
