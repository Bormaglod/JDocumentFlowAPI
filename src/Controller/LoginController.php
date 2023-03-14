<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Core\{HttpResponse, JwtAuth};
use App\Connection\PostgresConnection;

class LoginController extends DatabaseController {

   function login(Request $request, Response $response, $args) {
      $version = $this->getVersion($request);
      if ($version == 0) {
         return $response->withJson(
            [
               'error_code' => JwtAuth::API_VERSION_REQUIRED, 
               'message' => 'Необходимо указать версию API.'
            ], 
            HttpResponse::HTTP_BAD_REQUEST);
      }

      $params = $request->getParsedBody();

      try {
         $sql = 'select id, www_name, www_password from user_alias where name = :name';
         $user = PostgresConnection::get('guest', 'guest')->getRow($sql, ['name' => $params['username']]);
         if ($user and $user->www_password == $params['password']) {
   
            $psw = '';
            switch ($user->www_name) {
               case 'www_user':
                  $psw = getenv('WWW_USER');
                  break;
            }
            
            $jwt = JwtAuth::createToken($user->id, $params['username']);
            
            $sql = 'call login(:user_id, :token)';
            PostgresConnection::get($user->www_name, $psw)->perform($sql, ['token' => $jwt, 'user_id' => $user->id]);

            return $response->withJson(['token' => $jwt]);
         } else {
            return $response->withJson(
               [
                  'error_code' => JwtAuth::USER_NOT_REGISTERED, 
                  'message' => 'Пользователь ' . $params['username'] . ' не зарегестрирован.'
               ], 
               HttpResponse::HTTP_BAD_REQUEST);
         }
      } catch (PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
      }
   }
}

?>