<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Get Senders List class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Application\Maileva;

use MaarchCourrier\ExternalExport\Domain\Maileva\MailevaTemplate;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\MailevaApiServiceInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\MailevaTemplateRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaConfigNotFoundProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaCouldNotGetAuthTokenProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaIsDisabledProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaTemplateNotFoundProblem;

class MailevaEreGetSendersList
{
    public function __construct(
        private readonly MailevaConfiguration $mailevaConfiguration,
        private readonly MailevaApiServiceInterface $mailevaApiService,
        private readonly MailevaTemplateRepositoryInterface $mailevaTemplateRepository
    ) {
    }

    /**
     * @param int|null $shippingTemplateId
     * @param array|null $templateAccount
     * @return array
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaCouldNotGetAuthTokenProblem
     * @throws MailevaIsDisabledProblem
     * @throws MailevaTemplateNotFoundProblem
     */
    public function execute(?int $shippingTemplateId, ?array $templateAccount): array
    {
        if (
            empty($shippingTemplateId) &&
            (empty($templateAccount) || empty($templateAccount['id']) || empty($templateAccount['password']))
        ) {
            throw new MailevaCouldNotGetAuthTokenProblem('templateId or templateAccount must be set');
        }

        $config = $this->mailevaConfiguration->getMailevaConfiguration();

        if ($shippingTemplateId) {
            $template = $this->mailevaTemplateRepository->getById($shippingTemplateId);
            if ($template === null) {
                throw new MailevaTemplateNotFoundProblem();
            }
        } else {
            $template = (new MailevaTemplate())
                ->setId(-1)
                ->setOptions([])
                ->setAccount($templateAccount);
        }

        $this->mailevaApiService->setConfig($config, $template);
        $this->mailevaApiService->getAuthToken();
        $senders = $this->mailevaApiService->getEreSenders();

        return array_map(function ($sender) {
            return [
                'id'        => $sender['id'],
                'lastname'  => $sender['last_name'],
                'firstname' => $sender['first_name'],
                'company'   => $sender['company_name'],
                'email'     => $sender['email'],
            ];
        }, $senders);
    }
}
