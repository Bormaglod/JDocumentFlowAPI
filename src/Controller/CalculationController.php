<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\{
   ServerRequestInterface as Request,
   ResponseInterface as Response
};
use App\Query\QueryBuilder;
use App\Exception\BadParameterException;

class CalculationController extends DatasetController {
   
   const NAME     = 'calculation';
   const API_NAME = 'calculations';

   function getByOwner(Request $request, Response $response, $args) {
      try {
         $connect = $this->connect($request);
         $params = $request->getParams();

         $onlyApproved = false;
         if ($this->isValidParam('only-approved', $params)) {
            $onlyApproved = $this->getBoolParam('only-approved', $params);
         }
         
         $query = (new QueryBuilder())
            ->from(self::NAME)
            ->select('id', 'code', 'cost_price', 'profit_percent', 'profit_value', 'price', 'note', 'state', 'stimul_type', 'stimul_payment', 'date_approval')
            ->where('owner_id = :id');
         
         if ($onlyApproved) {
            $query->where("state = 'approved'::calculation_state");
         }

         $data = $connect->execute($query, $args);
   
         return $response->withJson($this->getFormattedData($data));
      } catch (\PDOException $e) {
         return $this->getResponseException($response, $e);
      } catch (AccessException | VersionException | BadParameterException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   protected function createQuery(QueryBuilder $query, array $params) {
      return $query
         ->select('id', 'code', 'cost_price', 'profit_percent', 'profit_value', 'price', 'note', 'state', 'stimul_type', 'stimul_payment', 'date_approval')
         ->from(self::NAME);
   }

   protected function getEntityName() {
      return self::NAME;
   }

   protected function getApiName() {
      return self::API_NAME;
   }
}

?>