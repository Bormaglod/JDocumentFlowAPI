<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;
use App\Exception\BadParameterException;
use App\Controller\CalculationController;
use App\Core\Relationship;

class GoodsController extends ProductController {
    const NAME = 'goods';
    
    private $show_calc = false;

    protected function getEntityName() {
        return self::NAME;
    }

    protected function createQuery(QueryBuilder $query, array $params) {
        $balance = (new QueryBuilder())
            ->select('reference_id', 'sum(amount) AS product_balance')
            ->from('balance_goods')
            ->groupBy('reference_id');

        $query = parent::createQuery($query, $params);
        $query
            ->selectWithAlias('is_service', 'note', 'length', 'width', 'height')
            ->select('mb.product_balance')
            ->leftJoin($balance, 'mb', "mb.reference_id = {$query->getAlias()}.id");
        return $query;
    }

    protected function addIncludeInfo(QueryBuilder $query, string $include): void {
        if ($include == CalculationController::NAME) {
            $this->show_calc = true;

            $query = $query
                ->select('c.id as c_id', 'c.code as c_code', 'c.cost_price as c_cost_price', 'c.profit_percent as c_profit_percent', 'c.profit_value as c_profit_value', 'c.price as c_price', 'c.note as c_note', 'c.state as c_state', 'c.stimul_type as c_stimul_type', 'c.stimul_payment as c_stimul_payment', 'c.date_approval as c_date_approval')
                ->leftJoin('calculation as c', "c.id = {$query->getAlias()}.calculation_id");
        }
        else {
            parent::addIncludeInfo($query, $include);
        }
    }

    protected function getCountBaseAttributes(): int {
        return parent::getCountBaseAttributes() + 6;
    }

    protected function getRelations(array $row): array {
        $res = parent::getRelations($row);
        if ($this->show_calc) {
            $calc = [];
            foreach (array_slice($row, $this->getCountBaseAttributes() + 4, 11) as $key => $value) {
                $calc[mb_substr($key, 2)] = $value;
            }

            $res[] = new Relationship($calc['id'], CalculationController::API_NAME, CalculationController::NAME, $calc);
        }
        
        return $res;
    }
}

?>