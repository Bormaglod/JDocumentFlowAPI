<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;
use App\Exception\BadParameterException;
use App\Controller\MeasurementController;

class ProductController extends DirectoryController {
    private $show_measurement = false;

    protected function createQuery(QueryBuilder $query, array $params) {
        if ($this->isValidParam('include', $params)) {
            $this->show_measurement = true;

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

        $query
            ->select('p.id', 'p.code', 'p.item_name', 'p.is_folder', 'p.price', 'p.vat', 'p.measurement_id', 'p.weight')
            ->from($this->getEntityName(), 'p');
        
        return parent::createQuery($query, $params);
    }

    protected function getBaseAttributes(array $row): array {
        if ($this->show_measurement) {
            $attrs = array_filter($row, function($x) { return $x != 'abbreviation'; }, ARRAY_FILTER_USE_KEY);
            return $this->getNormalRow($attrs, 1);
        }
        else {
            return parent::getBaseAttributes($row);
        }
     }

    protected function getRelations(array $row): array {
        if ($this->show_measurement) {
            $measurement = $this->getNormalRow(array_slice($row, 0, 4), 0);
            $data = [ 'data' => [ 'type' => MeasurementController::API_NAME, 'id' => $measurement['id']]];
            $rels = [ 'measurement' => $data];
            return [ 'rel_name' => $rels, 'include' => $measurement, 'api_name' => MeasurementController::API_NAME ];
        }
        else {
            return parent::getRelations($row);
        }
     }
}

?>