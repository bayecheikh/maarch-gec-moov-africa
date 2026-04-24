<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Shipping Controller
 * @author dev@maarch.org
 */

namespace Shipping\controllers;

use Attachment\models\AttachmentModel;
use DateTime;
use Entity\models\EntityModel;
use Exception;
use MaarchCourrier\Authorization\Domain\Problem\MainResourceOutOfPerimeterProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterMustBeInteger;
use MaarchCourrier\ExternalExport\Application\Maileva\GetMailevaShippingInfo;
use MaarchCourrier\ExternalExport\Infrastructure\Maileva\Repository\MailevaTemplateRepository;
use MaarchCourrier\ExternalExport\Infrastructure\Maileva\Repository\ShippingRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use Resource\controllers\ResController;
use Respect\Validation\Validator;
use Shipping\models\ShippingModel;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use User\models\UserModel;

class ShippingController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public static function getByResId(Request $request, Response $response, array $args): Response
    {
        if (
            !Validator::intVal()->validate($args['resId']) ||
            !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])
        ) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $attachments = AttachmentModel::get([
            'select' => ['res_id'],
            'where'  => ['res_id_master = ?'],
            'data'   => [$args['resId']]
        ]);
        $attachments = array_column($attachments, 'res_id');

        $where = '(document_id = ? and document_type = ?)';
        $data = [$args['resId'], 'resource'];

        if (!empty($attachments)) {
            $where .= ' or (document_id in (?) and document_type = ?)';
            $data[] = $attachments;
            $data[] = 'attachment';
        }

        $shippingsModel = ShippingModel::get([
            'select' => ['*'],
            'where'  => [$where],
            'data'   => $data
        ]);

        $shippings = [];

        foreach ($shippingsModel as $shipping) {
            $recipientEntityLabel = EntityModel::getById([
                'id'     => $shipping['recipient_entity_id'],
                'select' => ['entity_label']
            ]);
            $recipientEntityLabel = $recipientEntityLabel['entity_label'];
            $recipients = json_decode($shipping['recipients'], true);
            $contacts = [];
            foreach ($recipients as $recipient) {
                $contacts[] = ['company' => $recipient[1], 'contactLabel' => $recipient[2]];
            }

            $shippings[] = [
                'id'                   => $shipping['id'],
                'documentId'           => $shipping['document_id'],
                'documentType'         => $shipping['document_type'],
                'userId'               => $shipping['user_id'],
                'userLabel'            => UserModel::getLabelledUserById(['id' => $shipping['user_id']]),
                'fee'                  => $shipping['fee'],
                'creationDate'         => $shipping['creation_date'],
                'recipientEntityId'    => $shipping['recipient_entity_id'],
                'recipientEntityLabel' => $recipientEntityLabel,
                'recipients'           => $contacts
            ];
        }

        return $response->withJson($shippings);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ParameterMustBeInteger
     */
    public function getMailevaShippingInfoByResId(Request $request, Response $response, array $args): Response
    {
        $shippingRepository = new ShippingRepository(new MailevaTemplateRepository());
        $userRepository = new UserRepository();

        if (!Validator::intVal()->validate($args['resId'])) {
            throw new ParameterMustBeInteger('Resource ID');
        }

        if (!ResController::hasRightByResId(['resId' => [(int)$args['resId']], 'userId' => $GLOBALS['id']])) {
            throw new MainResourceOutOfPerimeterProblem();
        }

        $useCase = new GetMailevaShippingInfo($shippingRepository, $userRepository);
        $result = $useCase->execute((int)$args['resId'], $GLOBALS['id']);

        return $response->withJson($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function getShippingAttachmentsList(Request $request, Response $response, array $args): Response
    {
        if (empty($args['sendingId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route sendingId is empty']);
        }

        $shipping = ShippingModel::get([
            'select' => ['id', 'document_id', 'document_type', 'attachments'],
            'where'  => ['sending_id = ?'],
            'data'   => [$args['sendingId']]
        ]);
        if (empty($shipping[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'No shipping with this sending id']);
        }
        $shipping = $shipping[0];

        $resId = $shipping['document_id'];
        if ($shipping['document_type'] == 'attachment') {
            $referencedAttachment = AttachmentModel::getById([
                'id'     => $shipping['document_id'],
                'select' => ['res_id', 'res_id_master']
            ]);
            if (empty($referencedAttachment)) {
                return $response->withStatus(400)->withJson(['No attachment with this id']);
            }
            $resId = $referencedAttachment['res_id_master'];
        }
        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $shipping['attachments'] = json_decode($shipping['attachments'], true);
        if (!Validator::arrayType()->each(Validator::intType())->validate($shipping['attachments'])) {
            return $response->withStatus(500)->withJson(['errors' => 'Shipping attachments are improperly saved']);
        }
        $attachments = [];
        foreach ($shipping['attachments'] as $attachmentId) {
            $attachment = AttachmentModel::getById([
                'id'     => $attachmentId,
                'select' => ['res_id', 'title', 'creation_date', 'attachment_type', 'filesize']
            ]);
            if (empty($attachment)) {
                continue;
            }
            $attachments[] = [
                'resId'          => $attachment['res_id'],
                'title'          => $attachment['title'],
                'creationDate'   => $attachment['creation_date'],
                'attachmentType' => $attachment['attachment_type'],
                'filesize'       => $attachment['filesize']
            ];
        }
        return $response->withJson(['attachments' => $attachments]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function getHistory(Request $request, Response $response, array $args): Response
    {
        if (empty($args['sendingId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route sendingId is empty']);
        }

        $shipping = ShippingModel::get([
            'select' => ['id', 'document_id', 'document_type', 'history'],
            'where'  => ['sending_id = ?'],
            'data'   => [$args['sendingId']]
        ]);
        if (empty($shipping[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'No shipping with this sending id']);
        }
        $shipping = $shipping[0];
        $shipping['history'] = json_decode($shipping['history'], true);

        $resId = $shipping['document_id'];
        if ($shipping['document_type'] == 'attachment') {
            $referencedAttachment = AttachmentModel::getById([
                'id'     => $shipping['document_id'],
                'select' => ['res_id', 'res_id_master']
            ]);
            if (empty($referencedAttachment)) {
                return $response->withStatus(400)->withJson(['No attachment with this id']);
            }
            $resId = $referencedAttachment['res_id_master'];
        }
        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        foreach ($shipping['history'] as $key => $history) {
            $shipping['history'][$key]['eventDate'] = (new DateTime($history['eventDate']))
                ->format('Y-m-d H:i:s');
        }

        return $response->withJson(['history' => $shipping['history']]);
    }
}
