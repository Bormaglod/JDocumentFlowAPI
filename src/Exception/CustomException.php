<?php
// Copyright © 2018-2020 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Exception;

use Exception;
use App\Controller\DatabaseController;

class CustomException extends Exception
{
   public function getMessageData() {
      return ['error_code' => $this->getCode(), 'message' => $this->getMessage()];
   }

   public function getHttpCode() {
      return DatabaseController::HTTP_INTERNAL_SERVER_ERROR;
   }
}
?>