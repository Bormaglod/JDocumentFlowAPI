<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;

class OkopfController extends DatasetController {
   private $name = 'okopf';

   protected function getEntityName() {
      return $this->name;
   }

   /*protected function getQuery(array $params) {
      return (new QueryBuilder())
         ->select('id', 'code', 'item_name')
         ->from($this->name);
   }*/
   protected function createQuery(QueryBuilder $query, array $params) {
      if ($this->isValidParam('include', $params)) {
         throw new BadParameterException('An endpoint does not support the include parameter.');
      }
      
      return $query
         ->select('id', 'code', 'item_name')
         ->from($this->name);
   }

   /*protected function getQueryById(array $params) {
      return (new QueryBuilder())
         ->select('id', 'code', 'item_name')
         ->from($this->name)
         ->where('id = :id');
   }*/

   protected function getFields() {
      return ['code', 'name'];
   }
}

?>