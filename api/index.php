<?php
// Copyright © 2018-2019 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0
session_start();

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Http\StatusCode as StatusCode;

require '../vendor/autoload.php';
require 'db.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$app = new \Slim\App(["settings" => $config]);

$app->post('/login', 'login_user');
$app->get('/logout', 'logout_user');
$app->get('/menu', 'get_menu');
$app->get('/pictures', 'get_pictures');
$app->get('/commands/{ref}', 'get_commands');
$app->post('/groups', 'add_group');
$app->put('/groups/{id}', 'update_group');
$app->delete('/groups/{id}', 'delete_group');
$app->get('/users', 'get_users');

$app->options('/{routes:.+}', function ($request, $response, $args) {
   return $response;
});

$app->add(function ($req, $res, $next) {
   $response = $next($req, $res);
   return $response
           ->withHeader('Access-Control-Allow-Origin', '*')//'http://document-flow.home:4201')
           ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
           ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
   $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
   return $handler($req, $res);
});

$app->run();

function get_version(Request $request) {
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

function update_expired_time() {
   $_SESSION['time_expired'] = strtotime('+1 day');
}

function array_select($menu, $parent_id) {
	$result = [];
	foreach($menu as $item) { 
		if (is_null($parent_id)) {
			if (is_null($item['parent_id'])) {
            $result[] = $item;
         }
		} else {
			if ($item['parent_id'] == $parent_id) {
            $result[] = $item;
         }
		}
	}
		
	return $result;
}

function generate_menu($menu, $parent_id  = null) {
	$result = array_select($menu, $parent_id);
	for($i = 0; $i < count($result); $i++) {
		$submenu = generate_menu($menu, $result[$i]['id']);
		$result[$i]['nodes'] = $submenu;
	}
		
	return $result;
}

function check_token($params, Response $response) {
   if (array_key_exists('token', $params)) {
      if ($_SESSION['token'] !== $params['token']) {
         return $response->withJson(['error_code' => -0x01200, 'message' => 'Указан неверный идентификатор пользователя'], StatusCode::HTTP_UNAUTHORIZED);
      }

      if (time() > $_SESSION['time_expired']) {
         return $response->withJson(['error_code' => -0x01201, 'message' => 'Вас слишком долго не было. Сессия была закрыта.'], StatusCode::HTTP_UNAUTHORIZED);
      }

      return $response;
   }

   return $response->withJson(['error_code' => -0x01202, 'message' => 'Запрос требует указания идентификатора пользователя.'], StatusCode::HTTP_UNAUTHORIZED);
}

function check_version($version, Response $response)
{
   if ($version == 0) {
      return $response->withJson(['error_code' => -0x1100, 'message' => 'Необходимо указать версию API.'], StatusCode::HTTP_BAD_REQUEST);
   }

   return $response;
}

function get_rows($select, $param_values = []) {
   $connect = (new Db(array("user" => $_SESSION['user'], "password" => $_SESSION['password'])))->getConnect();
   $query = $connect->prepare($select);

   // получение всех параметров запоса вида ':параметр'
   preg_match_all('/(?<!:):([a-zA-Z]{1}[a-zA-Z_0-9]*)/', $select, $param_names, PREG_SET_ORDER);

   foreach ($param_names as $name) {
       $param_type = array_key_exists($name[1], $param_values) ? PDO::PARAM_STR : PDO::PARAM_NULL;
       $query->bindParam($name[0], $param_values[$name[1]], $param_type);
   }

   $query->execute();
   $total_rows = $query->rowCount();
   $rows = $query->fetchAll();

   $data = [ 'total_rows' => $total_rows, 'rows' => $rows ];
   return $data;
}

function get_users(Request $request, Response $response) {
   $version = get_version($request);
   $response = check_version($version, $response);

   if ($response->isClientError()) return $response;

   try {
      $connect = (new Db(["user" => 'guest', "password" => 'guest']))->getConnect();
      $query = $connect->prepare('select * from client where not administrator and parent_id is not null');
      $query->execute();

      $total_rows = $query->rowCount();
      $rows = $query->fetchAll();

      $data = [ 'total_rows' => $total_rows, 'rows' => $rows ];
      return $response->withJson($data);
   } catch (PDOException $e) {
      return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
   }
}

function login_user(Request $request, Response $response) {
   $version = get_version($request);
   $response = check_version($version, $response);
   
   if ($response->isClientError()) return $response;

   $params = $request->getParsedBody();
   
   try {
      $connect = (new Db(["user" => 'guest', "password" => 'guest']))->getConnect();
      
      $query = $connect->prepare('select pg_name from client where name = :name');
      $query->bindParam(':name', $params['username']);
      $query->execute();
      $user = $query->fetchColumn();

      if ($user) {
         $connect = (new Db(array("user" => $user, "password" => $params['password'])))->getConnect();
      
         $query = $connect->prepare('select login()');
         $query->execute();
 
         $_SESSION['user'] = $user;
         $_SESSION['password'] = $params['password'];
         $_SESSION['token'] = md5(uniqid($user, true));
         update_expired_time();

         return $response->withJson(['token' => $_SESSION['token']]);
      } else {
         return $response->withJson(['error_code' => -0x01000, 'message' => 'Пользователь ' . $params['username'] . ' не зарегестрирован.'], StatusCode::HTTP_BAD_REQUEST);
      }
   } catch (PDOException $e) {
      return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
   }
}

function logout_user(Request $request, Response $response) {
   $version = get_version($request);
   $response = check_version($version, $response);
   
   if ($response->isClientError()) return $response;

   $params = $request->getQueryParams();
   $response = check_token($params, $response);
   if ($response->isOk()) {
      $connect = (new Db(array("user" => $user, "password" => $params['password'])))->getConnect();
      
      $query = $connect->prepare('select logout()');

      try {
         $query->execute();
         unset($_SESSION['user']);
         unset($_SESSION['password']);
         unset($_SESSION['token']);
         unset($_SESSION['time_expired']);
      } catch (PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
      }
   }

   return $response;
}

function get_menu(Request $request, Response $response) {
   $version = get_version($request);
   $response = check_version($version, $response);
   
   if ($response->isClientError()) return $response;

   $params = $request->getQueryParams();
   $response = check_token($params, $response);
   if ($response->isOk()) {
      try {
         $connect = (new Db(array("user" => $_SESSION['user'], "password" => $_SESSION['password'])))->getConnect();
         $query = $connect->prepare('select * from select_menu()');
	      $query->execute();
	
         $menu = $query->fetchAll();

         update_expired_time();

         return $response->withJson(generate_menu($menu));
      } catch (PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
      }
   }
   
   return $response;
}

function get_commands(Request $request, Response $response, $args) {
   $version = get_version($request);
   $response = check_version($version, $response);
   
   if ($response->isClientError()) return $response;

   $params = $request->getQueryParams();
   $response = check_token($params, $response);
   if ($response->isOk()) {
      if (!array_key_exists('type', $params)) {
         return $response->withJson(['error_code' => -0x02000, 'message' => 'не указан тип команды.'], StatusCode::HTTP_NOT_FOUND);
      }

      $type = $params['type'];
      if ($type !== 'id' && $type !== 'code') {
         return $response->withJson(['error_code' => -0x02001, 'message' => 'Неизвестный тип команды.'], StatusCode::HTTP_NOT_FOUND);
      }

      try {
         $connect = (new Db(array("user" => $_SESSION['user'], "password" => $_SESSION['password'])))->getConnect();
         $query = $connect->prepare("select * from get_command_by_$type(:command)");
	      $query->bindParam(':command', $args['ref']);
	      $query->execute();
	
	      $result = $query->fetch();

         if ($result) {
   	      $schema_data = json_decode($result['schema_data']);
	         foreach ($schema_data->viewer->datasets as $db) {
		         if ($db->name == $schema_data->viewer->master) {
                  $select = $db->select;
                
                  $query = $connect->prepare('select * from get_info_table(:code_table)');
                  $query->bindParam(':code_table', $db->name);
                  $query->execute();

                  $info = $query->fetch();
                  $db->info = $info;
			         break;
      	      }
	         }
	
	         $result['schema_data'] = $schema_data;
            return $response->withJson($result);
         } else {
            return $response->withJson(['error_code' => -0x02002, 'message' => 'Несуществующий идентификатор команды.'], StatusCode::HTTP_NOT_FOUND);
         }
      } catch (PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
      }
   }

   return $response;
}

function get_pictures(Request $request, Response $response) {
   $version = get_version($request);
   $response = check_version($version, $response);
   
   if ($response->isClientError()) return $response;

   $params = $request->getQueryParams();
   $response = check_token($params, $response);
   if ($response->isOk()) {
      try {
         $rows = get_rows("select * from picture_select() where parent_id = get_constant('picture.status')::uuid");
         update_expired_time();

         return $response->withJson($rows);
      } catch (PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
      }
   }

   return $response;
}

function add_group(Request $request, Response $response) {
   $version = get_version($request);
   $response = check_version($version, $response);
   
   if ($response->isClientError()) return $response;

   $params = $request->getParsedBody();
   $response = check_token($params, $response);
   if ($response->isOk()) {
      $connect = (new Db(array("user" => $_SESSION['user'], "password" => $_SESSION['password'])))->getConnect();
      $query = $connect->prepare('select * from group_create(:kind, :code, :name, :parent)');
	   $query->bindParam(':kind', $params['kind']);
      $query->bindParam(':code', $params['code']);
      $query->bindParam(':name', $params['name']);
      $type = PDO::PARAM_NULL;
      if (array_key_exists('parent', $params)) {
         if ($params['parent'] !== 'top') {
            $type = PDO::PARAM_STR;
         }
      }

      $query->bindParam(':parent', $params['parent'], $type);

      try {
         $query->execute();
         $result = $query->fetch();
         if ($result) {
            return $response->withJson($result, StatusCode::HTTP_CREATED);
         } else {
            return $response->withJson(['error_code' => -0x02010, 'message' => 'Ошибка при создании группы.'], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
         }
      } catch (PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
      }
   }

   return $response;
}

function update_group(Request $request, Response $response, $args) {
   $version = get_version($request);
   $response = check_version($version, $response);
   
   if ($response->isClientError()) return $response;

   $params = $request->getParsedBody();
   $response = check_token($params, $response);
   if ($response->isOk()) {
      $connect = (new Db(array("user" => $_SESSION['user'], "password" => $_SESSION['password'])))->getConnect();
      $query = $connect->prepare('select * from group_update(:id, :code, :name)');
	   $query->bindParam(':id', $args['id']);
      $query->bindParam(':code', $params['code']);
      $query->bindParam(':name', $params['name']);

      try {
         $query->execute();
         $result = $query->fetch();
         if ($result) {
            return $response->withJson($result, StatusCode::HTTP_OK);
         } else {
            return $response->withJson(['error_code' => -0x02011, 'message' => 'Ошибка при обновлении группы.'], StatusCode::HTTP_NO_CONTENT);
         }
      } catch (PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
      }
   }

   return $response;
}

function delete_group(Request $request, Response $response, $args) {
   $version = get_version($request);
   $response = check_version($version, $response);
   
   if ($response->isClientError()) return $response;

   $params = $request->getQueryParams();
   $response = check_token($params, $response);
   if ($response->isOk()) {
      $connect = (new Db(array("user" => $_SESSION['user'], "password" => $_SESSION['password'])))->getConnect();
      $query = $connect->prepare('select * from group_delete(:id)');
	   $query->bindParam(':id', $args['id']);

      try {
         $query->execute();
         return $response->withJson([], StatusCode::HTTP_NO_CONTENT);
      } catch (PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage()], StatusCode::HTTP_INTERNAL_SERVER_ERROR);
      }
   }

   return $response;
}
?>