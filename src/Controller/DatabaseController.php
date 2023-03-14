<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Exception\{AccessException, VersionException};
use App\Connection\PostgresConnection;
use App\Core\{AppCode, ExceptionHelper, HttpResponse, JwtAuth};

class DatabaseController {

   protected function getVersion(Request $request) {
      $accept = strtolower(str_replace(' ', '', $request->getHeaderLine('Accept')));
   
      $accept_list = explode(',', $accept);
      foreach ($accept_list as $item) {
         $item_list = explode(';', $item);
         if ($item_list[0] === 'application/vnd.document-flow.api+json') {
            $version = explode('=', $item_list[1]);
            return $version[1];
         }
      }
   
      return 0;
   }

   protected function checkAccess(Request $request) {
      $version = $this->getVersion($request);
      if ($version == 0) 
         throw new VersionException('Необходимо указать версию API.', AppCode::API_VERSION_REQUIRED);
    
      $access = JwtAuth::checkToken($request);
      if ($access) {
         throw new AccessException($access['message'], $access['error_code']);
      }
      else {
         $decoded = $request->getAttribute('token');

         $sql = 'select www_name, www_password from user_alias where id = :id';
         $user = PostgresConnection::get('guest', 'guest')->getRow($sql, ['id' => $decoded['user']->id]);

         if ($user) {
            $psw = '';
            switch ($user->www_name) {
               case 'www_user':
                  $psw = getenv('WWW_USER');
                  break;
            }

            return array('user_name' => $user->www_name, 'password' => $psw);
         }
         else
         {
            throw new AccessException('Invalid token.', AppCode::INVALID_TOKEN);
         }
      }
   }

   protected function connect(Request $request) {
      return PostgresConnection::get($this->checkAccess($request));
   }

   protected function getResponseException(Response $response, \PDOException $exception) {
      return $response->withJson(
         [
            'error_code' => $exception->getCode(), 
            'message' => $exception->getMessage(), 
            'extended' => ExceptionHelper::getExtendErrors($exception)
         ], 
         HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
   }
}

?>