<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Query\QueryBuilder;
use App\Exception\BadParameterException;

class CalculationController extends DatasetController {
   const NAME     = 'calculation';
   const API_NAME = 'calculations';

   protected function getEntityName() {
      return self::NAME;
   }

   protected function getApiName() {
      return self::API_NAME;
   }
}

?>