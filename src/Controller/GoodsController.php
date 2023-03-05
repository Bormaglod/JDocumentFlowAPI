<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;

class GoodsController extends ProductController {
    const NAME = 'goods';

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
            ->select('is_service', 'calculation_id', 'note', 'length', 'width', 'height', 'mb.product_balance')
            ->leftJoin($balance, 'mb', "mb.reference_id = {$query->getAlias()}.id");
        return $query;
    }
}

?>