<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Core;

class ExceptionHelper {
   
   static function getExtendErrors(\PDOException $exception) {
      if (is_numeric($exception->getCode())) {
         $re = '/SQLSTATE\[(.+?)\]:\s+(.+?)\s+ОШИБКА:\s+(.+)\s+DETAIL:\s+(.+)/';
         $name = 'detail';
      } else {
         $re = '/SQLSTATE\[(.+?)\]:\s+(.+?)\s+ОШИБКА:\s+(.+)\s+CONTEXT:\s+(.+)/';
         $name = 'context';
      }

      preg_match_all($re, $exception->getMessage(), $matches, PREG_SET_ORDER, 0);
   
      if (count($matches) > 0) {
         $msg = $matches[0][3];
         if (substr($matches[0][3], 0, 1) == '{') {
            $msg = json_decode($matches[0][3]);
         }
         
         return ['sqlstate' => $matches[0][1], 'name' => $matches[0][2], 'message' => $msg, $name => $matches[0][4]];
      }
      else
         return [];
   }
}

?>