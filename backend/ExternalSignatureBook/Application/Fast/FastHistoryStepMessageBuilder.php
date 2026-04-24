<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Fast History Step Message Builder class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalSignatureBook\Application\Fast;

use DateTimeImmutable;
use Exception;
use MaarchCourrier\History\Domain\Port\HistoryRepositoryInterface;

class FastHistoryStepMessageBuilder
{
    public function __construct(private readonly HistoryRepositoryInterface $historyRepository)
    {
    }

    private array $messages = [
        'Préparé'                   => _FAST_PARAPHEUR_MESSAGE_PREPARE,
        'Métadonnées définies'      => _FAST_PARAPHEUR_MESSAGE_META_DATA_SET,
        'Envoyé pour signature'     => _FAST_PARAPHEUR_MESSAGE_SENT_FOR_SIGNATURE,
        'Signé'                     => _FAST_PARAPHEUR_MESSAGE_SIGNED,
        'Refusé'                    => _FAST_PARAPHEUR_MESSAGE_REFUSED,
        'Signature validée'         => _FAST_PARAPHEUR_MESSAGE_SIGNATURE_VALIDATED,
        'signature rejetée'         => _FAST_PARAPHEUR_MESSAGE_SIGNATURE_REJECTED,
        'Envoyé pour visa'          => _FAST_PARAPHEUR_MESSAGE_SENT_FOR_VISA,
        'Validé'                    => _FAST_PARAPHEUR_MESSAGE_VALIDATED,
        'Rejeté'                    => _FAST_PARAPHEUR_MESSAGE_REJECTED,
        "En cours d'envoi"          => _FAST_PARAPHEUR_MESSAGE_SENDING,
        'Envoyé à FAST'             => _FAST_PARAPHEUR_MESSAGE_SENT_TO_FAST,
        'Transmis à Hélios'         => _FAST_PARAPHEUR_MESSAGE_SENT_TO_HELIOS,
        'Acquittement Hélios'       => _FAST_PARAPHEUR_MESSAGE_ACQUITTED_HELIOS,
        "Échec de l'envoi à FAST"   => _FAST_PARAPHEUR_MESSAGE_FAILURE_SEND_FAST,
        'Échec du traitement FAST'  => _FAST_PARAPHEUR_MESSAGE_FAILURE_TREATMENT_FAST,
        "Échec de l'envoi à Hélios" => _FAST_PARAPHEUR_MESSAGE_FAILURE_SEND_HELIOS,
        'Informations OTP définies' => _FAST_PARAPHEUR_MESSAGE_OTP_INFO_SET,
        'OTP validé'                => _FAST_PARAPHEUR_MESSAGE_OTP_VALIDATED,
        'Classé'                    => _FAST_PARAPHEUR_MESSAGE_ARCHIVED,
        'Archivé'                   => _FAST_PARAPHEUR_MESSAGE_CLASSIFIED,
        'Document remplacé'         => _FAST_PARAPHEUR_MESSAGE_REPLACED
    ];

    /**
     * Builds a history entry containing the event timestamp and a fully formatted message.
     *
     * @param bool $isMainResource Whether this is the main document (true) or an attachment (false).
     * @param string $historyIdentifier Identifier of the document or attachment.
     * @param string $status Status key corresponding to a message template in {@see self::$messages}.
     * @param DateTimeImmutable $date Date and time of the event.
     * @param string $userNameThatPerformedAction Username of the user who performed the action.
     *
     * @return null|array{eventDate: string, message: string}
     *        Returns null if the history exists or an array with:
     *          - eventDate: the event date/time as “Y-m-d H:i:s”
     *          - message: the full "[FastParapheur] DateTime : Header 'Identifier' - status message" formatted string
     * @throws Exception From database errors
     */
    public function buildMessage(
        bool $isMainResource,
        string $historyIdentifier,
        string $status,
        DateTimeImmutable $date,
        string $userNameThatPerformedAction
    ): ?array {
        $dateString = $date->format('Y-m-d H:i:s');
        $headerMsg = $isMainResource ? _DOCUMENT : _ATTACHMENT;
        $messageText = $this->messages[$status] ?? _UNKNOWN_STATUS;
        $messageText = str_replace('[USER_NAME]', $userNameThatPerformedAction, $messageText);

        $isExist = $this->historyRepository->doesHistoryRecordExistFromInfoMsgAndEventDate($messageText, $dateString);

        if ($isExist) {
            return null;
        }

        return [
            'eventDate' => $dateString,
            // Format du message : [FastParapheur] DateTime : Header 'Identifier' - status message
            'message'   => sprintf(
                "[FastParapheur] %s : %s '%s' - %s",
                $dateString,
                $headerMsg,
                $historyIdentifier,
                $messageText
            )
        ];
    }
}
