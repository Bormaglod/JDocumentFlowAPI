<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;

class MaterialController extends ProductController {
    const NAME     = 'material';
    const API_NAME = 'materials';

    protected function getEntityName() {
        return self::NAME;
    }

    protected function getApiName() {
        return self::API_NAME;
    }

    protected function createQuery(QueryBuilder $query, array $params) {
        $balance = (new QueryBuilder())
            ->select('reference_id', 'sum(amount) AS product_balance')
            ->from('balance_material')
            ->groupBy('reference_id');

        $query = parent::createQuery($query, $params);
        $query
            ->select('min_order', 'ext_article', 'mb.product_balance')
            ->leftJoin($balance, 'mb', "mb.reference_id = {$query->getAlias()}.id");
        return $query;
    }

    protected function getCountBaseAttributes(): int {
        return parent::getCountBaseAttributes() + 3;
    }
}

?>