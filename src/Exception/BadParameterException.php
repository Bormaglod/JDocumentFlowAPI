<?php
// Copyright © 2018-2020 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Exception;

use Exception;
use App\Controller\DatabaseController;

class BadParameterException extends CustomException
{
   function __construct($message) {
      parent::__construct($message, DatabaseController::BAD_PARAMETER);
   }

   public function getHttpCode() {
      return DatabaseController::HTTP_BAD_REQUEST;
   }
}

?>