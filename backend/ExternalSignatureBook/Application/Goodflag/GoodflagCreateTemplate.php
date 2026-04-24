<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Goodflag Create Template class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Goodflag;

use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;
use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagConfig;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\GoodflagPrivilege;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Port\GoodflagApiServiceInterface;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConfigNotFoundProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagConsentPageInvalidProblem;
use MaarchCourrier\ExternalSignatureBook\Domain\Goodflag\Problem\GoodflagSignatureProfileInvalidProblem;

class GoodflagCreateTemplate
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $configurationRepository,
        private readonly GoodflagCheckTemplate $checkTemplate,
        private readonly GoodflagApiServiceInterface $goodflagApiService
    ) {
    }

    /**
     * @param array $body
     * @return void
     * @throws GoodflagConfigNotFoundProblem
     * @throws GoodflagConsentPageInvalidProblem
     * @throws GoodflagSignatureProfileInvalidProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     */
    public function execute(array $body): void
    {
        if ($this->checkTemplate->checkTemplate($body)) {
            $currentConfig = $this->configurationRepository->getByPrivilege(new GoodflagPrivilege());
            if ($currentConfig == null) {
                throw new GoodflagConfigNotFoundProblem();
            }

            $data = $currentConfig->getValue();
            $data['templates'][] = [
                'id'                 => $this->generateGoodflagTemplateId(
                    $body['label'],
                    $body['consentPageId'],
                    $body['signatureProfileId']
                ),
                'label'              => $body['label'],
                'description'        => $body['description'] ?? '',
                'consentPageId'      => $body['consentPageId'],
                'signatureProfileId' => $body['signatureProfileId']
            ];
            $this->configurationRepository->updateByPrivilege(new GoodflagPrivilege(), $data);

            // Création du webhook côté Goodflag si celui-ci n'existe pas
            if (
                !empty($body['webhookEndpoint']) &&
                !$this->goodflagApiService->isWebhookExists($body['webhookEndpoint'])
            ) {
                $goodflagConfig = (new GoodflagConfig())
                    ->setUrl($data['url'])
                    ->setAccessToken($data['accessToken']);
                $this->goodflagApiService->loadConfig();

                $notifiedEvents = [
                    'recipientRefused',
                    'recipientFinished',
                    'workflowStopped',
                    'workflowFinished'
                ];
                $this->goodflagApiService->createWebhook($body['webhookEndpoint'], $notifiedEvents);
            }
        }
    }

    /**
     * @param string $label
     * @param string $consentPageId
     * @param string $signatureProfileId
     * @return string
     */
    private function generateGoodflagTemplateId(
        string $label,
        string $consentPageId,
        string $signatureProfileId
    ): string {
        // Normaliser le label (garder seulement les premiers mots)
        $normalizedLabel = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $label));
        $shortLabel = substr($normalizedLabel, 0, 20); // Limiter la longueur

        // Créer un hash des IDs avec SHA-256
        $hash = substr(hash('sha256', $consentPageId . $signatureProfileId), 0, 8);

        // Ajouter un timestamp pour l'unicité
        $timestamp = date('YmdHis');

        return $shortLabel . '_' . $hash . '_' . $timestamp;
    }
}
