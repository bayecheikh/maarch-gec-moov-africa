<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Retrieve Config class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Fast;

use MaarchCourrier\ExternalSignatureBook\Domain\ExternalSignatureBookType;
use MaarchCourrier\ExternalSignatureBook\Domain\Fast\FastParapheurConfig;
use MaarchCourrier\ExternalSignatureBook\Domain\Fast\Port\FastParapheurConfigInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Fast\Problem\FastParapheurMissingConfigProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Port\ExternalSignatureBookConfigServiceInterface;
use SimpleXMLElement;

class RetrieveConfig
{
    public function __construct(
        private readonly ExternalSignatureBookConfigServiceInterface $externalSignatureBookConfigService
    ) {
    }

    /**
     * @return FastParapheurConfigInterface
     * @throws FastParapheurMissingConfigProblem
     */
    public function get(): FastParapheurConfigInterface
    {
        $xmlConfig = $this->externalSignatureBookConfigService->getConfigById(ExternalSignatureBookType::FAST);

        $integratedWorkflow = filter_var(
            $xmlConfig->integratedWorkflow ?? 'false',
            FILTER_VALIDATE_BOOLEAN
        );
        $optionOtp = filter_var(
            $xmlConfig->optionOtp ?? 'false',
            FILTER_VALIDATE_BOOLEAN
        );

        $this->configChecks($integratedWorkflow, $xmlConfig);

        $signatureModes = [];
        foreach ($xmlConfig->signatureModes->mode ?? [] as $mode) {
            $signatureModes[] = [
                'id'    => (string)$mode->id,
                'label' => (string)$mode->label,
            ];
        }

        $workflowTypes = [];
        foreach ($xmlConfig->workflowTypes->type ?? [] as $type) {
            $workflowTypes[] = [
                'id'    => (string)$type->id,
                'label' => (string)$type->label,
            ];
        }

        return (new FastParapheurConfig())
            ->setIsEnabled(
                $this->externalSignatureBookConfigService->getEnable() === ExternalSignatureBookType::FAST->value
            )
            ->setIntegratedWorkflow($integratedWorkflow)
            ->setSubscriberId($xmlConfig->subscriberId)
            ->setOptionOtp($optionOtp)
            ->setSignatureModes($signatureModes)
            ->setWorkflowTypes($workflowTypes)
            ->setUrl($xmlConfig->url)
            ->setCertPath($xmlConfig->certPath)
            ->setCertPass($xmlConfig->certPass ?? '')
            ->setCertType($xmlConfig->certType)
            ->setValidatedState($xmlConfig->validatedState)
            ->setRefusedState($xmlConfig->refusedState)
            ->setValidatedVisaState($xmlConfig->validatedVisaState)
            ->setRefusedVisaState($xmlConfig->refusedVisaState);
    }

    /**
     * @param bool             $isIntegratedWorkflow
     * @param SimpleXMLElement $xmlConfig
     *
     * @return void
     * @throws FastParapheurMissingConfigProblem
     */
    private function configChecks(bool $isIntegratedWorkflow, SimpleXMLElement $xmlConfig): void
    {
        if (empty($xmlConfig->subscriberId ?? null)) {
            throw new FastParapheurMissingConfigProblem('subscriberId');
        } elseif (empty($xmlConfig->url ?? null)) {
            throw new FastParapheurMissingConfigProblem('url');
        } elseif (empty($xmlConfig->certPath ?? null)) {
            throw new FastParapheurMissingConfigProblem('certPath');
        } elseif (empty($xmlConfig->certType ?? null)) {
            throw new FastParapheurMissingConfigProblem('certType');
        } elseif ($isIntegratedWorkflow && empty($xmlConfig->signatureModes ?? null)) {
            throw new FastParapheurMissingConfigProblem('signatureModes');
        } elseif ($isIntegratedWorkflow && empty($xmlConfig->workflowTypes ?? null)) {
            throw new FastParapheurMissingConfigProblem('workflowTypes');
        } elseif (empty($xmlConfig->validatedState ?? null)) {
            throw new FastParapheurMissingConfigProblem('validatedState');
        } elseif (empty($xmlConfig->refusedState ?? null)) {
            throw new FastParapheurMissingConfigProblem('refusedState');
        } elseif (empty($xmlConfig->validatedVisaState ?? null)) {
            throw new FastParapheurMissingConfigProblem('validatedVisaState');
        } elseif (empty($xmlConfig->refusedVisaState ?? null)) {
            throw new FastParapheurMissingConfigProblem('refusedVisaState');
        }
    }
}
