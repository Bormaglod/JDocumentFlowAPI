<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;
use App\Exception\BadParameterException;

class DirectoryController extends DatasetController {
    protected function createQuery(QueryBuilder $query, array $params) {
        $showFolders = true;
        if ($this->isValidParam('show-folders', $params)) {
            $this->checkBoolParam('show-folders', $params);

            $showFolders = $params['show-folders'] == 'true';
        }

        if (!$showFolders) {
            $query->where("not {$query->getAlias()}.is_folder");
        }

        if ($this->isValidParam("folder-id", $params)) {
            if (isset($params["folder-id"]) and strlen($params["folder-id"]) > 0) {
                $re = '/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}(?(1)\})$/mi';
                if (preg_match($re, $params["folder-id"]) !== 1) {
                   throw new BadParameterException('Параметр folder-id должен содержать корректное значение GUID', DatabaseController::BAD_PARAMETER);
                }
    
                $id = $params["folder-id"];
                $query->where("{$query->getAlias()}.parent_id = '{$id}'");
             } else {
                $query->where("{$query->getAlias()}.parent_id is null");
             }
        }

        return $query;
    }
}

?>