<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;

class MaterialController extends ProductController {
    private $name = 'material';
    private $apiName = 'materials';

    protected function getEntityName() {
        return $this->name;
    }

    protected function getApiName() {
        return $this->apiName;
    }

    protected function createQuery(QueryBuilder $query, array $params) {
        $query = parent::createQuery($query, $params);
        $query->select('min_order', 'ext_article');
        return $query;
    }
}

?>