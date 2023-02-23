<?php
// Copyright © 2018-2020 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Exception;

class AccessException extends CustomException
{
   public function getHttpCode() {
      return 401;
   }
}

?>