<?php
// Copyright © 2018-2020 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Exception\BadParameterException;

class GoodsController extends DatasetController {
   protected function getEntityName(Request $request) {
      return 'goods';
   }

   protected function getSQLQuery(Request $request) {
      $params = $request->getParsedBody();
      if (array_key_exists("folder-id", $params)) {
         $cond = [];
         if (isset($params["folder-id"]) and strlen($params["folder-id"]) > 0) {
            $re = '/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}(?(1)\})$/mi';
            if (preg_match($re, $params["folder-id"]) !== 1) {
               throw new BadParameterException('Параметр folder-id должен содержать корректное значение GUID', DatabaseController::BAD_PARAMETER);
            }

            $id = $params["folder-id"];
            $cond[] = "g.parent_id = '{$id}'";
         } else {
            $cond[] = 'g.parent_id is null';
         }
         
         if (!array_key_exists('show-folders', $params)) {
            $cond[] = 'g.status_id != 500';
         }

         return $this->getSQL($cond);
      } else {
         if (array_key_exists('show-folders', $params)) {
            return $this->getSQLRecursive();
         }
         else {
            return $this->getSQLRecursive(['cte.status_id != 500']);
         }
      }
   }

   protected function getSQLQueryById(Request $request) {
      return $this->getSQLRecursive(['cte.id = :id']);
   }

   protected function getFields(Request $request) {
      return [ 'code', 'name', 'measurement_id', 'price', 'tax', 'min_order', 'is_service' ];
   }

   private function getSQLRecursive($conditions = null)
   {
      $sql = "with recursive cte as
         (
            select g.id, g.status_id, g.parent_id, g.code, g.name, g.measurement_id, g.price, g.tax, g.min_order, g.is_service, cast(g.name as text) as namepath from goods g where parent_id is null
            union all
            select g.id, g.status_id, g.parent_id, g.code, g.name, g.measurement_id, g.price, g.tax, g.min_order, g.is_service, case when g.status_id = 500 then cte.namepath || '/' || cast(g.name as text) else cte.namepath end from goods g join cte on (cte.id = g.parent_id)
         )
         select cte.id, cte.status_id, s.note as status_name, cte.code, cte.name, cte.measurement_id, m.name as measurement_name, cte.price, cte.tax, cte.min_order, cte.is_service, cte.namepath from cte join status s on (s.id = cte.status_id) left join measurement m on (m.id = cte.measurement_id)";
      if (isset($conditions) and count($conditions) > 0) {
         $sql .= " where " . implode(' and ', $conditions);
      }

      return $sql;
   }

   private function getSQL($conditions = null) {
      $sql = "select g.id, g.status_id, g.parent_id, g.code, g.name, g.measurement_id, g.price, g.tax, g.min_order, g.is_service, cast(g.name as text) as namepath from goods g";
      if (isset($conditions) and count($conditions) > 0) {
         $sql .= " where " . implode(' and ', $conditions);
      }

      return $sql;
   }
}

?>