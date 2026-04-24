<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Fast Parapheur Config class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Fast;

use MaarchCourrier\ExternalSignatureBook\Domain\Fast\Port\FastParapheurConfigInterface;

class FastParapheurConfig implements FastParapheurConfigInterface
{
    private bool $isEnabled = false;
    private bool $integratedWorkflow;
    private string $subscriberId;
    private bool $optionOtp;
    private array $signatureModes;
    private array $workflowTypes;
    private string $url;
    private string $certPath;
    private string $certPass;
    private string $certType;
    private string $validatedState;
    private string $refusedState;
    private string $validatedVisaState;
    private string $refusedVisaState;

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): FastParapheurConfig
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    public function isIntegratedWorkflow(): bool
    {
        return $this->integratedWorkflow;
    }

    public function setIntegratedWorkflow(bool $value): FastParapheurConfigInterface
    {
        $this->integratedWorkflow = $value;
        return $this;
    }

    public function getSubscriberId(): string
    {
        return $this->subscriberId;
    }

    public function setSubscriberId(string $id): FastParapheurConfigInterface
    {
        $this->subscriberId = $id;
        return $this;
    }

    public function isOptionOtp(): bool
    {
        return $this->optionOtp;
    }

    public function setOptionOtp(bool $value): FastParapheurConfigInterface
    {
        $this->optionOtp = $value;
        return $this;
    }

    public function getSignatureModes(): array
    {
        return $this->signatureModes;
    }

    public function setSignatureModes(array $modes): FastParapheurConfigInterface
    {
        $this->signatureModes = $modes;
        return $this;
    }

    public function getWorkflowTypes(): array
    {
        return $this->workflowTypes;
    }

    public function setWorkflowTypes(array $workflowTypes): FastParapheurConfigInterface
    {
        $this->workflowTypes = $workflowTypes;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): FastParapheurConfigInterface
    {
        $this->url = $url;
        return $this;
    }

    public function getCertPath(): string
    {
        return $this->certPath;
    }

    public function setCertPath(string $path): FastParapheurConfigInterface
    {
        $this->certPath = $path;
        return $this;
    }

    public function getCertPass(): string
    {
        return $this->certPass;
    }

    public function setCertPass(string $pass): FastParapheurConfigInterface
    {
        $this->certPass = $pass;
        return $this;
    }

    public function getCertType(): string
    {
        return $this->certType;
    }

    public function setCertType(string $type): FastParapheurConfigInterface
    {
        $this->certType = $type;
        return $this;
    }

    public function getValidatedState(): string
    {
        return $this->validatedState;
    }

    public function setValidatedState(string $state): FastParapheurConfigInterface
    {
        $this->validatedState = $state;
        return $this;
    }

    public function getRefusedState(): string
    {
        return $this->refusedState;
    }

    public function setRefusedState(string $state): FastParapheurConfigInterface
    {
        $this->refusedState = $state;
        return $this;
    }

    public function getValidatedVisaState(): string
    {
        return $this->validatedVisaState;
    }

    public function setValidatedVisaState(string $state): FastParapheurConfigInterface
    {
        $this->validatedVisaState = $state;
        return $this;
    }

    public function getRefusedVisaState(): string
    {
        return $this->refusedVisaState;
    }

    public function setRefusedVisaState(string $state): FastParapheurConfigInterface
    {
        $this->refusedVisaState = $state;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'enabled'            => $this->isEnabled,
            'integratedWorkflow' => $this->integratedWorkflow,
            'subscriberId'       => $this->subscriberId,
            'optionOtp'          => $this->optionOtp,
            'signatureModes'     => $this->signatureModes,
            'workflowTypes'      => $this->workflowTypes,
            'url'                => $this->url,
            'certPath'           => $this->certPath,
            'certPass'           => $this->certPass,
            'certType'           => $this->certType,
            'validatedState'     => $this->validatedState,
            'refusedState'       => $this->refusedState,
            'validatedVisaState' => $this->validatedVisaState,
            'refusedVisaState'   => $this->refusedVisaState
        ];
    }
}
