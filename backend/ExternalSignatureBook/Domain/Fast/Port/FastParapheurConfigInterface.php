<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief Fast Parapheur Config Interface
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Domain\Fast\Port;

use JsonSerializable;

interface FastParapheurConfigInterface extends JsonSerializable
{
    public function isIntegratedWorkflow(): bool;
    public function setIntegratedWorkflow(bool $value): self;
    public function getSubscriberId(): string;
    public function setSubscriberId(string $id): self;
    public function isOptionOtp(): bool;
    public function setOptionOtp(bool $value): self;
    public function getSignatureModes(): array;
    public function setSignatureModes(array $modes): self;
    public function getWorkflowTypes(): array;
    public function setWorkflowTypes(array $workflowTypes): self;
    public function getUrl(): string;
    public function setUrl(string $url): self;
    public function getCertPath(): string;
    public function setCertPath(string $path): self;
    public function getCertPass(): string;
    public function setCertPass(string $pass): self;
    public function getCertType(): string;
    public function setCertType(string $type): self;
    public function getValidatedState(): string;
    public function setValidatedState(string $state): self;
    public function getRefusedState(): string;
    public function setRefusedState(string $state): self;
    public function getValidatedVisaState(): string;
    public function setValidatedVisaState(string $state): self;
    public function getRefusedVisaState(): string;
    public function setRefusedVisaState(string $state): self;
}
