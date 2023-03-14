<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Core;

class AppCode {
   
   const TOKEN_LIFETIME_EXPIRED   = 600000;
   const AUTHORIZATION_REQUIRED   = 600001;
   const SIGN_VERIFICATION_FAILED = 600002;
   const INVALID_TOKEN            = 600003;
   const API_VERSION_REQUIRED     = 601000;
   const OBJECT_NOT_EXISTS        = 602000;
   const USER_NOT_REGISTERED      = 603000;
   const BAD_PARAMETER            = 604000;
   const UNKNOWN_ERROR            = 605000;
}

?>