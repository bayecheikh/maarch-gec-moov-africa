<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 * @brief   FirstLevelController
 *
 * @author  dev <dev@maarch.org>
 * @ingroup core
 */

namespace Doctype\controllers;

use Exception;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use Respect\Validation\Validator;
use Doctype\models\FirstLevelModel;
use Doctype\models\SecondLevelModel;
use Doctype\models\DoctypeModel;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class FirstLevelController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function getTree(Request $request, Response $response): Response
    {
        return $response->withJson(['structure' => FirstLevelController::getTreeFunction()]);
    }

    /**
     * @return array
     * @throws Exception
     */
    public static function getTreeFunction(): array
    {
        $firstLevels = FirstLevelModel::get([
            'where'   => ['enabled = ?'],
            'data'    => ['Y'],
            'orderBy' => ['doctypes_first_level_label asc']
        ]);
        $secondLevels = SecondLevelModel::get([
            'where'   => ['enabled = ?'],
            'data'    => ['Y'],
            'orderBy' => ['doctypes_second_level_label asc']
        ]);
        $docTypes = DoctypeModel::get([
            'where'   => ['enabled = ?'],
            'data'    => ['Y'],
            'orderBy' => ['description asc']
        ]);

        $structure = [];
        foreach ($firstLevels as $firstLevelValue) {
            $firstLevelValue['id'] = 'firstlevel_' . $firstLevelValue['doctypes_first_level_id'];
            $firstLevelValue['text'] = $firstLevelValue['doctypes_first_level_label'];
            $firstLevelValue['parent'] = '#';
            $firstLevelValue['state']['opened'] = true;
            $structure[] = $firstLevelValue;
        }
        foreach ($secondLevels as $secondLevelValue) {
            $secondLevelValue['id'] = 'secondlevel_' . $secondLevelValue['doctypes_second_level_id'];
            $secondLevelValue['text'] = $secondLevelValue['doctypes_second_level_label'];
            $secondLevelValue['parent'] = 'firstlevel_' . $secondLevelValue['doctypes_first_level_id'];
            $structure[] = $secondLevelValue;
        }
        foreach ($docTypes as $doctypeValue) {
            $doctypeValue['id'] = $doctypeValue['type_id'];
            $doctypeValue['text'] = $doctypeValue['description'];
            $doctypeValue['parent'] = 'secondlevel_' . $doctypeValue['doctypes_second_level_id'];
            $doctypeValue['icon'] = 'fa fa-copy';
            $structure[] = $doctypeValue;
        }

        return $structure;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $aArgs
     * @return Response
     * @throws Exception
     */
    public function getById(Request $request, Response $response, array $aArgs): Response
    {
        if (!Validator::notEmpty()->validate($aArgs['id']) || !Validator::intVal()->validate($aArgs['id'])) {
            return $response->withStatus(500)->withJson(['errors' => 'wrong format for id']);
        }

        $obj = [];
        $obj['firstLevel'] = FirstLevelModel::getById(['id' => $aArgs['id']]);

        $hasChildren = SecondLevelModel::get([
            'select' => [1],
            'where'  => ['doctypes_first_level_id = ?', 'enabled = ?'],
            'data'   => [$aArgs['id'], 'Y']
        ]);
        $obj['firstLevel']['hasChildren'] = !empty($hasChildren);

        $obj['firstLevel']['enabled'] = $obj['firstLevel']['enabled'] == 'Y';

        return $response->withJson($obj);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function initDoctypes(Request $request, Response $response): Response
    {
        $obj['firstLevel'] = FirstLevelModel::get([
            'select'   => ['doctypes_first_level_id', 'doctypes_first_level_label'],
            'where'    => ['enabled = ?'],
            'data'     => ['Y'],
            'order_by' => ['doctypes_first_level_id asc']
        ]);
        $obj['secondLevel'] = SecondLevelModel::get([
            'select'   => ['doctypes_second_level_id', 'doctypes_second_level_label'],
            'where'    => ['enabled = ?'],
            'data'     => ['Y'],
            'order_by' => ['doctypes_second_level_label asc']
        ]);
        $obj['indexes'] = [];

        return $response->withJson($obj);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function create(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_architecture', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParsedBody();
        $data = $this->manageValue($data);

        $errors = $this->control($data, 'create');
        if (!empty($errors)) {
            return $response->withStatus(500)->withJson(['errors' => $errors]);
        }

        unset($data['foldertype_id']);
        $firstLevelId = FirstLevelModel::create($data);

        HistoryController::add([
            'tableName' => 'doctypes_first_level',
            'recordId'  => $firstLevelId,
            'eventType' => 'ADD',
            'eventId'   => 'structureadd',
            'info'      => _DOCTYPE_FIRSTLEVEL_ADDED . ' : ' . $data['doctypes_first_level_label'],
        ]);

        return $response->withJson([
            'firstLevelId' => $firstLevelId,
            'doctypeTree'  => FirstLevelController::getTreeFunction()
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $aArgs
     * @return Response
     * @throws Exception
     */
    public function update(Request $request, Response $response, array $aArgs): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_architecture', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $obj = $request->getParsedBody();
        $obj['doctypes_first_level_id'] = $aArgs['id'];
        unset($obj['hasChildren']);

        $obj = $this->manageValue($obj);
        $errors = $this->control($obj, 'update');

        if (!empty($errors)) {
            return $response->withStatus(500)->withJson(['errors' => $errors]);
        }

        unset($obj['foldertype_id']);
        FirstLevelModel::update($obj);

        HistoryController::add([
            'tableName' => 'doctypes_first_level',
            'recordId'  => $obj['doctypes_first_level_id'],
            'eventType' => 'UP',
            'eventId'   => 'structureup',
            'info'      => _DOCTYPE_FIRSTLEVEL_UPDATED . ' : ' . $obj['doctypes_first_level_label'],
        ]);

        return $response->withJson([
            'firstLevelId' => $obj,
            'doctypeTree'  => FirstLevelController::getTreeFunction()
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $aArgs
     * @return Response
     * @throws Exception
     */
    public function delete(Request $request, Response $response, array $aArgs): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_architecture', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->validate($aArgs['id'])) {
            return $response->withStatus(500)->withJson(['errors' => 'Id is not a numeric']);
        }

        FirstLevelModel::update(['doctypes_first_level_id' => $aArgs['id'], 'enabled' => 'N']);
        SecondLevelModel::disabledFirstLevel(['doctypes_first_level_id' => $aArgs['id'], 'enabled' => 'N']);
        DoctypeModel::disabledFirstLevel(['doctypes_first_level_id' => $aArgs['id'], 'enabled' => 'N']);
        $firstLevel = FirstLevelModel::getById(['id' => $aArgs['id']]);

        HistoryController::add([
            'tableName' => 'doctypes_first_level',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'eventId'   => 'structuredel',
            'info'      => _DOCTYPE_FIRSTLEVEL_DELETED . ' : ' . $firstLevel['doctypes_first_level_label'],
        ]);

        return $response->withJson([
            'firstLevelDeleted' => $firstLevel,
            'doctypeTree'       => FirstLevelController::getTreeFunction()
        ]);
    }

    /**
     * @param array $aArgs
     * @param string $mode
     * @return array
     * @throws Exception
     */
    protected function control(array $aArgs, string $mode): array
    {
        $errors = [];

        if ($mode == 'update') {
            if (!Validator::intVal()->validate($aArgs['doctypes_first_level_id'])) {
                $errors[] = 'Id is not a numeric';
            } else {
                $obj = FirstLevelModel::getById(['id' => $aArgs['doctypes_first_level_id']]);
            }

            if (empty($obj)) {
                $errors[] = 'Id ' . $aArgs['doctypes_first_level_id'] . ' does not exists';
            }
        }

        if (
            !Validator::notEmpty()->validate($aArgs['doctypes_first_level_label']) ||
            !Validator::length(1, 255)->validate($aArgs['doctypes_first_level_label'])
        ) {
            $errors[] = 'Invalid doctypes_first_level_label';
        }

        if (empty($aArgs['enabled'])) {
            $aArgs['enabled'] = 'Y';
        }

        if ($aArgs['enabled'] != 'Y' && $aArgs['enabled'] != 'N') {
            $errors[] = 'Invalid enabled value';
        }

        return $errors;
    }

    /**
     * @param array $request
     * @return array
     */
    protected function manageValue(array $request): array
    {
        foreach ($request as $key => $value) {
            if ($key == 'enabled') {
                if (empty($value)) {
                    $request[$key] = 'N';
                } else {
                    $request[$key] = 'Y';
                }
            }
        }

        return $request;
    }
}
