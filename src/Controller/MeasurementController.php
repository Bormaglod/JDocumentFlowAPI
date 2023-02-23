<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;

class MeasurementController extends DatasetController {
   private $name = 'measurement';

   protected function getEntityName() {
      return $this->name;
   }

   protected function getQuery(array $params) {
      return (new QueryBuilder())
         ->select('id', 'code', 'item_name', 'abbreviation')
         ->from($this->name);
   }

   protected function getQueryById(array $params) {
      return (new QueryBuilder())
         ->select('id', 'code', 'item_name', 'abbreviation')
         ->from($this->name)
         ->where('id = :id');
   }

   protected function getFields() {
      return ['code', 'item_name', 'abbreviation'];
   }
}

?>