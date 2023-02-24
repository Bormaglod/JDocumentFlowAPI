<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Connection\PostgresConnection;
use App\Exception\AccessException;
use App\Exception\VersionException;
use App\Exception\NotImplementedException;
use App\Exception\BadParameterException;

class DatasetController extends DatabaseController {
   public function get(Request $request, Response $response, $args) {
      try {
         $connect = PostgresConnection::get($this->checkAccess($request));
         $params = $request->getParams();
         $query = $this->getQuery($params);
         
         if ($this->isValidParam('show-deleted', $params)) {
            if (!array_search($params['show-deleted'], array('', 'true', "false"))) {
               return $response->withJson(['error_code' => DatabaseController::BAD_PARAMETER, 'message' => 'Параметр show-deleted должен иметь значение "true", "false" или пустая строка.']);
            }

            if ($params['show-deleted'] == 'true') {
               $query->where('not deleted');
            }
         }
   
         if ($this->isValidParam('offset', $params)) {
            if (is_numeric($params['offset'])) {
               $query->offset($params['offset']);
            }
            else
            {
               return $response->withJson(['error_code' => DatabaseController::BAD_PARAMETER, 'message' => 'Параметр offset должен быть целым числм.']);
            }
         }
   
         if ($this->isValidParam('limit', $params)) {
            if (is_numeric($params['limit'])) {
               $query->limit($params['limit']);
            }
            else
            {
               return $response->withJson(['error_code' => DatabaseController::BAD_PARAMETER, 'message' => 'Параметр limit должен быть целым числм.']);
            }
         }

         if ($this->isValidParam('order-by', $params)) {
            $query->orderBy($params['order-by']);
         }

         $data = $connect->execute($query);

         return $response->withJson($data);
      } catch (\PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage(), 'extended' => $this->getExtendErrors($e)], self::HTTP_INTERNAL_SERVER_ERROR);
      } catch (AccessException | VersionException | BadParameterException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function getById(Request $request, Response $response, $args) {
      try {
         $connect = PostgresConnection::get($this->checkAccess($request));
         $query = $this->getQueryById($request->getParams());
         $data = $connect->execute($query, $args);
   
         if ($data['total_rows'] == 0) {
            return $response->withJson(['error_code' => self::OBJECT_NOT_EXISTS, 'message' => 'Объект с таким id не существует.'], self::HTTP_NOT_FOUND);
         }
   
         return $response->withJson($data);
      } catch (\PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage(), 'extended' => $this->getExtendErrors($e)], self::HTTP_INTERNAL_SERVER_ERROR);
      } catch (AccessException | VersionException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function post(Request $request, Response $response, $args) {
      try {
         $connect = PostgresConnection::get($this->checkAccess($request));
         $entity = $this->getEntityName();
         $id = $connect->insert($entity, $request->getParsedBody());
      
         $re = '/^.*\/(.*)$/m';

         preg_match_all($re, $request->getUri(), $matches, PREG_SET_ORDER, 0);

         return $response
            ->withStatus(self::HTTP_CREATED)
            ->withHeader('Location', "/{$matches[0][1]}/{$id}");
      } catch (\PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage(), 'extended' => $this->getExtendErrors($e)], self::HTTP_INTERNAL_SERVER_ERROR);
      } catch (AccessException | VersionException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function put(Request $request, Response $response, $args) {
      try {
         $connect = PostgresConnection::get($this->checkAccess($request));
         $connect->updateAll($this->getEntityName(), $args['id'], $this->getFields(), $request->getParsedBody());
   
         return $response->withStatus(self::HTTP_NO_CONTENT);
      } catch (\PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage(), 'extended' => $this->getExtendErrors($e)], self::HTTP_INTERNAL_SERVER_ERROR);
      } catch (AccessException | VersionException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function patch(Request $request, Response $response, $args) {
      try {
         $connect = PostgresConnection::get($this->checkAccess($request));
         $connect->update($this->getEntityName(), $args['id'], $request->getParsedBody());
   
         $query = $this->getQueryById($request->getParams());
         $data = $connect->execute($query, $args);

         return $response->withJson($data);
      } catch (\PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage(), 'extended' => $this->getExtendErrors($e)], self::HTTP_INTERNAL_SERVER_ERROR);
      } catch (AccessException | VersionException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function delete(Request $request, Response $response, $args) {
      try {
         $connect = PostgresConnection::get($this->checkAccess($request));
         $params = $request->getParams();
         if (array_key_exists('wipe', $params)) {
            $connect->wipe($this->getEntityName(), $args['id']);
         }
         else {
            $connect->delete($this->getEntityName(), $args['id']);
         }
   
         return $response->withStatus(self::HTTP_NO_CONTENT);
      } catch (\PDOException $e) {
         return $response->withJson(['error_code' => $e->getCode(), 'message' => $e->getMessage(), 'extended' => $this->getExtendErrors($e)], self::HTTP_INTERNAL_SERVER_ERROR);
      } catch (AccessException | VersionException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function isValidParam(string $paramName, array $params) {
      if (array_key_exists($paramName, $params)) {
         return !array_search($paramName, $this->getIgnoreParams());
      }

      return false;
   }

   protected function getEntityName() {
      throw new NotImplementedException('Функция getEntityName() не реализована.');
   }

   protected function getQuery(array $params) {
      throw new NotImplementedException('Функция getQuery() не реализована.');
   }

   protected function getQueryById(array $params) {
      throw new NotImplementedException('Функция getQueryById() не реализована.');
   }

   protected function getFields() {
      throw new NotImplementedException('Функция getFields() не реализована.');
   }

   protected function getIgnoreParams() {
      return array();
   }
}

?>