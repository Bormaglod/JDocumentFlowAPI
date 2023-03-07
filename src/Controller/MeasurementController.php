<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;

class MeasurementController extends DatasetController {
   const NAME     = 'measurement';
   const API_NAME = 'measurements';

   protected function getEntityName() {
      return self::NAME;
   }

   protected function getApiName() {
      return self::API_NAME;
   }

   protected function createQuery(QueryBuilder $query, array $params) {
      return $query
         ->select('id', 'code', 'item_name', 'abbreviation')
         ->from(self::NAME);
   }

   protected function getFields(): array {
      return ['code', 'item_name', 'abbreviation'];
   }
}

?>