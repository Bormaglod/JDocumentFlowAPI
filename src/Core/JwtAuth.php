<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Core;

use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\{JWT, ExpiredException, SignatureInvalidException};
use App\Code\AppCode;

class JwtAuth {

   static function createToken($id, $user_name)
   {
      $now = new \DateTime();
      $expire = new \DateTime("+1 month");
      
      $token = [
         "iat" => $now->getTimeStamp(),
         'exp' => $expire->getTimeStamp(), // время жизни токена
         'user' => [
            'id' => $id,                   // идентификатор пользователя
            'name' => $user_name           // имя пользователя
         ]   
      ];

      return JWT::encode($token, getenv('SECRET_KEY'), 'HS256');
   }

   static function checkToken(Request $request) {
      if ($request->hasHeader('Authorization')) {
         $auth = $request->getHeaderLine('Authorization');
         list($jwt) = sscanf($auth, 'Bearer %s');
         try {
            $token = JWT::decode($jwt, getenv('SECRET_KEY'), ['HS256']);
         }
         catch (ExpiredException $e) {
            return array('error_code' => AppCode::TOKEN_LIFETIME_EXPIRED, 'message' => 'Истекло время жизни токена.');
         }
         catch (SignatureInvalidException $e) {
            return array('error_code' => AppCode::SIGN_VERIFICATION_FAILED, 'message' => 'Invalid token. Signature verification failed.');
         }
         catch (\UnexpectedValueException $e) {
            return array('error_code' => AppCode::INVALID_TOKEN, 'message' => 'Invalid token.');
         }
      } else {
         return array('error_code' => AppCode::AUTHORIZATION_REQUIRED, 'message' => 'Для выполнения запроса необходима авторизация пользователя.');
      }
   }
}

?>