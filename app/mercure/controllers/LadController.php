<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief LadController
 * @author dev@maarch.org
 */

namespace Mercure\controllers;

use Configuration\models\ConfigurationModel;
use Contact\models\ContactModel;
use Exception;
use Group\controllers\PrivilegeController;
use MaarchCourrier\Mercure\Application\LaunchLadProcess;
use MaarchCourrier\Mercure\Infrastructure\Service\MercureService;
use setasign\Fpdi\Tcpdf\Fpdi;
use Respect\Validation\Validator;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;

class LadController
{
    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function isEnabled(Request $request, Response $response): Response
    {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        if (empty($configuration)) {
            return $response->withJson(['enabled' => false]);
        }

        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration['enabledLad'])) {
            return $response->withJson(['enabled' => false]);
        }

        return $response->withJson(['enabled' => true]);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws Exception
     */
    public function ladRequest(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (!Validator::notEmpty()->validate($body['encodedResource'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body encodedResource is empty']);
        }
        if (!Validator::notEmpty()->validate($body['extension'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body extension is empty']);
        }

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        if (empty($configuration)) {
            return $response->withStatus(400)->withJson(['errors' => 'Mercure configuration does not exist']);
        }

        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration['enabledLad'])) {
            return $response->withStatus(200)->withJson(['message' => 'Mercure LAD is not enabled']);
        }

        if (!empty($configuration['mwsLadPriority'])) {
            if (!Validator::notEmpty()->validate($body['filename'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Body filename is empty']);
            }

            $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => 'config/ladConfiguration.json']);
            if (empty($ladConfiguration)) {
                return $response->withStatus(400)->withJson([
                    'errors' => 'LAD configuration file does not exist'
                ]);
            }

            $ladResult = MwsController::launchLadMws([
                'encodedResource' => $body['encodedResource'],
                'filename'        => $body['filename']
            ]);
        } else {
            $launchLadProcess = new LaunchLadProcess(new MercureService());
            $ladResult = $launchLadProcess->execute($body['encodedResource'], $body['extension']);
        }

        if (!empty($ladResult['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $ladResult['errors']]);
        }

        return $response->withJson($ladResult);
    }

    /**
     * @return string
     */
    private static function generateTestPdf(): string
    {
        $libPath = CoreConfigModel::getFpdiPdfParserLibrary();
        if (file_exists($libPath)) {
            require_once($libPath);
        }
        $pdf = new Fpdi('P', 'pt');
        $pdf->setPrintHeader(false);
        $pdf->AddPage();

        $pdf->SetFont('', 'B', 14);
        $pdf->Write(5, 'Objet : Courrier test');

        $tmpDir = CoreConfigModel::getTmpPath();
        $filePathOnTmp = $tmpDir . 'fileTestLad.pdf';
        $pdf->Output($filePathOnTmp, 'F');

        return $filePathOnTmp;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws Exception
     */
    public function testLad(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_mercure', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        if (empty($configuration)) {
            $data = json_encode([
                'enabledLad'     => true,
                'mwsLadPriority' => false,
                'mws'            => [
                    'url'            => "",
                    'login'          => "",
                    'password'       => "",
                    'tokenMws'       => "",
                    'loginMaarch'    => "",
                    'passwordMaarch' => "",
                ]
            ]);
            ConfigurationModel::create(['value' => $data, 'privilege' => 'admin_mercure']);
        }

        $mercureService = new MercureService();

        $mercureSetupIsValid = $mercureService->isValidSetup();
        if (isset($mercureSetupIsValid['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $mercureSetupIsValid['errors']]);
        }

        $testFile = LadController::generateTestPdf();
        $encodedResource = base64_encode(file_get_contents($testFile));

        $launchLadProcess = new LaunchLadProcess($mercureService);
        $ladResult = $launchLadProcess->execute($encodedResource, 'pdf');

        if (!empty($ladResult['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $ladResult['errors']]);
        }

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws Exception
     */
    public static function getContactsIndexationState(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_mercure', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => 'config/ladConfiguration.json']);

        if (empty($ladConfiguration)) {
            return $response->withJson(['errors' => 'LAD configuration file does not exist']);
        }

        $customId = CoreConfigModel::getCustomId();
        $indexedContacts = ContactModel::get([
            'select' => ['COUNT(*)'],
            'where'  => ['lad_indexation = ? '],
            'data'   => [true]
        ]);
        $countIndexedContacts = (int)$indexedContacts[0]['count'];

        $allContacts = ContactModel::get([
            'select' => ['COUNT(*)']
        ]);
        $countAllContacts = (int)$allContacts[0]['count'];

        $lexDirectory = $ladConfiguration['config']['mercureLadDirectory'] .
            "/Lexiques/ContactsLexiques" . DIRECTORY_SEPARATOR . $customId;
        if (is_file($lexDirectory . DIRECTORY_SEPARATOR . "lastindexation.flag")) {
            $flagFile = fopen($lexDirectory . DIRECTORY_SEPARATOR . "lastindexation.flag", "r");
            if ($flagFile === false) {
                $dateIndexation = "";
            } else {
                $dateIndexation = fgets($flagFile);
                fclose($flagFile);
            }
        } else {
            $dateIndexation = "";
        }

        return $response->withJson([
            'dateIndexation'        => $dateIndexation,
            'countIndexedContacts'  => $countIndexedContacts,
            'countAllContacts'      => $countAllContacts,
            'pctIndexationContacts' => ($countIndexedContacts * 100) / $countAllContacts,
        ]);
    }
}
