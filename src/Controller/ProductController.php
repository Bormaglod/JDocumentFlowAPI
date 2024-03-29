<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;
use App\Exception\BadParameterException;
use App\Controller\MeasurementController;
use App\Core\Relationship;

class ProductController extends DirectoryController {
    
    private $show_measurement = false;

    protected function createQuery(QueryBuilder $query, array $params) {
        $query
            ->select('p.id', 'p.code', 'p.item_name', 'p.is_folder', 'p.price', 'p.vat', 'p.weight')
            ->from($this->getEntityName(), 'p');
        
        return parent::createQuery($query, $params);
    }

    protected function addIncludeInfo(QueryBuilder $query, string $include): void {
        if ($include == MeasurementController::NAME) {
            $this->show_measurement = true;

            $query = $query
                ->select('m.id as m_id', 'm.code as m_code', 'm.item_name as m_item_name', 'm.abbreviation as m_abbreviation')
                ->leftJoin('measurement as m', "m.id = {$query->getAlias()}.measurement_id");
        }
        else {
            throw new BadParameterException("The resource does not have an '$include' relationship path.");
        }
    }

    protected function getCountBaseAttributes(): int { 
        return 7;
    }

    protected function getBaseAttributes(array $row): array {
        return array_slice($row, 0, $this->getCountBaseAttributes());
     }

    protected function getRelations(array $row): array {
        if ($this->show_measurement) {
            $measurement = [];
            foreach (array_slice($row, $this->getCountBaseAttributes(), 4) as $key => $value) {
                $measurement[mb_substr($key, 2)] = $value;
            }

            return array(new Relationship($measurement['id'], MeasurementController::API_NAME, MeasurementController::NAME, $measurement));
        }
        else {
            return parent::getRelations($row);
        }
     }
}

?>