<?php
// Copyright © 2018-2020 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Exception;

use App\Controller\DatabaseController;

class VersionException extends CustomException
{
   public function getHttpCode() {
      return DatabaseController::HTTP_BAD_REQUEST;
   }
}
?>