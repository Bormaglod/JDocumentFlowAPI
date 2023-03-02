<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;
use App\Exception\BadParameterException;

class ProductController extends DirectoryController {
    protected function createQuery(QueryBuilder $query, array $params) {
        /*$showFolders = true;
        if ($this->isValidParam('show-folders', $params)) {
            $this->checkBoolParam('show-folders', $params);

            $showFolders = $params['show-folders'] == 'true';
        }*/
        $query = parent::createQuery($query, $params);

        if ($this->isValidParam('include', $params)) {
            if ($params['include'] == 'measurement') {
                $query = $query
                    ->select('m.id', 'm.code', 'm.item_name', 'm.abbreviation')
                    ->leftJoin('measurement as m', 'm.id = p.measurement_id');
            }
            else
            {
                throw new BadParameterException('Server does not support inclusion of resources from a path.');
            }
        }

        //$query = (new QueryBuilder())
        return $query
            ->select('p.id', 'p.code', 'p.item_name', 'p.is_folder', 'p.price', 'p.vat', 'p.measurement_id', 'p.weight'/*, 'm.item_name as measurement_name'*/)
            ->from($this->getEntityName(), 'p')/*
            ->leftJoin('measurement as m', 'm.id = p.measurement_id')*/;

        /*if (!$showFolders) {
            $query->where('not p.is_folder');
        }

        if ($this->isValidParam("folder-id", $params)) {
            if (isset($params["folder-id"]) and strlen($params["folder-id"]) > 0) {
                $re = '/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}(?(1)\})$/mi';
                if (preg_match($re, $params["folder-id"]) !== 1) {
                   throw new BadParameterException('Параметр folder-id должен содержать корректное значение GUID', DatabaseController::BAD_PARAMETER);
                }
    
                $id = $params["folder-id"];
                $query->where("p.parent_id = '{$id}'");
             } else {
                $query->where('p.parent_id is null');
             }
        }*/

        //return $query;
    }

    protected function getRelations(array $row): array {
        $rels = [ 'rel_name' => 'measurement' ];
        return $rels;
     }
}

?>