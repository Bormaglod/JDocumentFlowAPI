<?php
// Copyright © 2018-2020 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Exception;

use App\Core\HttpResponse;

class AccessException extends CustomException {

   function getHttpCode() {
      return HttpResponse::HTTP_UNAUTHORIZED;
   }
}

?>