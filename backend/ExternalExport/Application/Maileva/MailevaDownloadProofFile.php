<?php

namespace MaarchCourrier\ExternalExport\Application\Maileva;

use MaarchCourrier\Core\Domain\Problem\ParameterStringCanNotBeEmptyProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\MailevaApiServiceInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\ShippingRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaConfigNotFoundProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaIsDisabledProblem;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaShippingNotFoundProblem;

class MailevaDownloadProofFile
{
    public function __construct(
        private readonly MailevaConfiguration $mailevaConfiguration,
        private readonly MailevaApiServiceInterface $mailevaApiService,
        private readonly ShippingRepositoryInterface $shippingRepository
    ) {
    }

    /**
     * @param string $sendingId
     * @param string $recipientId
     * @return string
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     * @throws MailevaShippingNotFoundProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     */
    public function downloadDepositProof(string $sendingId, string $recipientId): string
    {
        $this->checkPrerequisites($sendingId, $recipientId);

        $curlResponse = $this->mailevaApiService->downloadDepositProof($sendingId, $recipientId);
        return base64_encode($curlResponse['response']);
    }

    /**
     * @param string $sendingId
     * @param string $recipientId
     * @return string
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     * @throws MailevaShippingNotFoundProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     */
    public function downloadProofOfReceipt(string $sendingId, string $recipientId): string
    {
        $this->checkPrerequisites($sendingId, $recipientId);

        $curlResponse = $this->mailevaApiService->downloadProofOfReceipt($sendingId, $recipientId);
        return base64_encode($curlResponse['response']);
    }

    /**
     * @param string $sendingId
     * @param string $recipientId
     * @return void
     * @throws MailevaConfigNotFoundProblem
     * @throws MailevaIsDisabledProblem
     * @throws MailevaShippingNotFoundProblem
     * @throws ParameterStringCanNotBeEmptyProblem
     */
    private function checkPrerequisites(string $sendingId, string $recipientId): void
    {
        if (empty($sendingId)) {
            throw new ParameterStringCanNotBeEmptyProblem('sendingId');
        }

        if (empty($recipientId)) {
            throw new ParameterStringCanNotBeEmptyProblem('recipientId');
        }

        $config = $this->mailevaConfiguration->getMailevaConfiguration();

        $shipping = $this->shippingRepository->getMailevaShippingBySendingId($sendingId);
        if ($shipping === null) {
            throw new MailevaShippingNotFoundProblem();
        }

        $this->mailevaApiService->setConfig($config, $shipping->getMailevaTemplate());
        $this->mailevaApiService->getAuthToken();
    }
}
