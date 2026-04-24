<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Get Maileva Shipping Info Class
 * @author dev@maarch.org
 */


namespace MaarchCourrier\ExternalExport\Application\Maileva;

use Exception;
use MaarchCourrier\Authorization\Domain\Problem\MainResourceOutOfPerimeterProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterMustBeInteger;
use MaarchCourrier\Core\Domain\User\Port\UserRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Port\ShippingRepositoryInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\Problem\MailevaMustHaveRecipientIdForEreShippingProblem;

class GetMailevaShippingInfo
{
    private ShippingRepositoryInterface $shippingRepository;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        ShippingRepositoryInterface $shippingRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->shippingRepository = $shippingRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @param  int  $resId
     * @param  int  $userId
     * @return array
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ParameterMustBeInteger
     * @throws Exception
     */
    public function execute(int $resId, int $userId): array
    {
        $shippings = $this->shippingRepository->getMailevaShippingInfoByResId($resId);

        $result = [];

        foreach ($shippings as $shipping) {
            $user = $this->userRepository->getUserById($shipping->getUser()->getId());
            $userLabel = '';
            if (!empty($user)) {
                $userLabel = $user->getFirstname() . ' ' . $user->getLastname();
            }

            $recipients = [];
            foreach ($shipping->getRecipients() as $recipient) {
                $recipientInfos = [];
                if (!empty($recipient['contactInfo'])) {
                    $recipientInfos = $recipient['contactInfo'];
                }

                if (empty($recipient['recipientId']) && $shipping->getOptions()['sendMode'] === 'ere') {
                    throw new MailevaMustHaveRecipientIdForEreShippingProblem($shipping->getId());
                }

                $recipients[] = [
                    'company'      => $recipientInfos['company'] ?? $recipient[1] ?? '',
                    'contactLabel' => implode(' ', array_filter([
                        $recipientInfos['firstname'] ?? '',
                        $recipientInfos['lastname'] ?? ''
                    ])) ?: ($recipient[2] ?? ''),
                    'recipientId'  => $recipient['recipientId'] ?? null,
                ];
            }

            $result[] = [
                'sendingId'    => $shipping->getSendingId(),
                'sendMode'     => $shipping->getOptions()['sendMode'],
                'userId'       => $shipping->getUser()->getId(),
                'userLabel'    => $userLabel,
                'creationDate' => $shipping->getCreationDate()->format('Y-m-d H:i:s'),
                'recipients'   => $recipients
            ];
        }

        return $result;
    }
}
