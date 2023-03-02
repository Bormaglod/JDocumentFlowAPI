<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;

class UsersController extends DatasetController {
   private $name = 'user_alias';

   protected function getEntityName() {
      return $this->name;
   }
   /*protected function getQuery(array $params) {
      return (new QueryBuilder())
         ->select('id', 'name', 'surname', 'first_name', 'middle_name')
         ->from('user_alias')
         ->where('not is_system');
   }*/
   protected function createQuery(QueryBuilder $query, array $params) {
      if ($this->isValidParam('include', $params)) {
         throw new BadParameterException('An endpoint does not support the include parameter.');
      }

      return $query
         ->select('id', 'name', 'surname', 'first_name', 'middle_name')
         ->from('user_alias')
         ->where('not is_system');
   }

   /*protected function getQueryById(array $params) {
      return (new QueryBuilder())
         ->select('id', 'name', 'surname', 'first_name', 'middle_name')
         ->from('user_alias')
         ->where('id = :id');
   }*/

   protected function getIgnoreParams()
   {
      return array('show-deleted');
   }
}

?>