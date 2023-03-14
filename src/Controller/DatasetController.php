<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Controller;

use Psr\Http\Message\{
   ServerRequestInterface as Request,
   ResponseInterface as Response
};
use App\Connection\PostgresConnection;
use App\Exception\{AccessException, BadParameterException, NotImplementedException, VersionException};
use App\Query\QueryBuilder;
use App\Core\{AppCode, ExceptionHelper, HttpResponse, JwtAuth};

class DatasetController extends DatabaseController {

   function get(Request $request, Response $response, $args) {
      try {
         $connect = $this->connect($request);
         $params = $request->getParams();

         $query = new QueryBuilder();
         $this->createQuery($query, $params);
         
         if ($this->isValidParam('show-deleted', $params)) {
            if ($this->getBoolParam('show-deleted', $params)) {
               $query->where("not {$query->getAlias()}.deleted");
            }
         }
   
         if ($this->isValidParam('offset', $params)) {
            if (is_numeric($params['offset'])) {
               $query->offset($params['offset']);
            }
            else
            {
               throw new BadParameterException('Параметр offset должен быть целым числом.');
            }
         }
   
         if ($this->isValidParam('limit', $params)) {
            if (is_numeric($params['limit'])) {
               $query->limit($params['limit']);
            }
            else
            {
               throw new BadParameterException('Параметр limit должен быть целым числом.');
            }
         }

         if ($this->isValidParam('order-by', $params)) {
            $query->orderBy($params['order-by']);
         }

         $this->checkIncludeParams($query, $params);

         $data = $connect->execute($query);

         return $response->withJson($this->getFormattedData($data));
      } catch (\PDOException $e) {
         return $this->getResponseException($response, $e);
      } catch (AccessException | VersionException | BadParameterException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function getById(Request $request, Response $response, $args) {
      try {
         $connect = $this->connect($request);
         $params = $request->getParams();
         
         $query = new QueryBuilder();
         $this->createQueryById($query, $params);

         $this->checkIncludeParams($query, $params);

         $data = $connect->execute($query, $args);
   
         if ($data['total_rows'] == 0) {
            return $response->withJson(
               [
                  'error_code' => AppCode::OBJECT_NOT_EXISTS, 
                  'message' => 'Объект с таким id не существует.'
               ], 
               HttpResponse::HTTP_NOT_FOUND);
         }
   
         return $response->withJson($this->getFormattedData($data));
      } catch (\PDOException $e) {
         return $this->getResponseException($response, $e);
      } catch (AccessException | VersionException | BadParameterException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function post(Request $request, Response $response, $args) {
      try {
         $connect = $this->connect($request);
         $entity = $this->getEntityName();
         $id = $connect->insert($entity, $request->getParsedBody());
      
         $re = '/^.*\/(.*)$/m';

         preg_match_all($re, $request->getUri(), $matches, PREG_SET_ORDER, 0);

         return $response
            ->withStatus(HttpResponse::HTTP_CREATED)
            ->withHeader('Location', "/{$matches[0][1]}/{$id}");
      } catch (\PDOException $e) {
         return $this->getResponseException($response, $e);
      } catch (AccessException | VersionException | BadParameterException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function put(Request $request, Response $response, $args) {
      try {
         $connect = $this->connect($request);
         $connect->updateAll($this->getEntityName(), $args['id'], $this->getFields(), $request->getParsedBody());
   
         return $response->withStatus(HttpResponse::HTTP_NO_CONTENT);
      } catch (\PDOException $e) {
         return $this->getResponseException($response, $e);
      } catch (AccessException | VersionException | BadParameterException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function patch(Request $request, Response $response, $args) {
      try {
         $connect = $this->connect($request);
         $connect->update($this->getEntityName(), $args['id'], $request->getParsedBody());
   
         $query = new QueryBuilder();
         $this->createQueryById($query, $request->getParams());

         $data = $connect->execute($query, $args);

         return $response->withJson($this->getFormattedData($data));
      } catch (\PDOException $e) {
         return $this->getResponseException($response, $e);
      } catch (AccessException | VersionException | BadParameterException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   function delete(Request $request, Response $response, $args) {
      try {
         $connect = $this->connect($request);
         $params = $request->getParams();

         $wipe = false;
         if ($this->isValidParam('wipe', $params)) {
            $wipe = $this->getBoolParam('wipe', $params);
         }

         if ($wipe) {
            $connect->wipe($this->getEntityName(), $args['id']);
         }
         else {
            $connect->delete($this->getEntityName(), $args['id']);
         }
   
         return $response->withStatus(HttpResponse::HTTP_NO_CONTENT);
      } catch (\PDOException $e) {
         return $this->getResponseException($response, $e);
      } catch (AccessException | VersionException | BadParameterException $e) {
         return $response->withJson($e->getMessageData(), $e->getHttpCode());
      }
   }

   protected function getFormattedData($source) {
      $meta = [ 'total_rows' => $source['total_rows']];
      $res = [ 'meta' => $meta ];
      $data = [];
      $included = [];
      foreach ($source['rows'] as $row) {
         $newData = $this->getRowData($this->getApiName(), $this->getBaseAttributes($row));
         $rels = $this->getRelations($row);

         if (count($rels) == 1) {
            $newData['relationships'] = $rels[0]->getRelationships();
         }
         else {
            $rs = [];
            foreach ($rels as $rel) {
               $rs[] = $rel->getRelationships();
            }

            if ($rs !== []) {
               $newData['relationships'] = $rs;
            }
         }

         foreach ($rels as $rel) {
            $key = array_search($rel->getId(), array_column($included, 'id'));
            if ($key === false) {
               $included[] = $rel->getInclude();
            }
         }

         $data[] = $newData;
      }

      $res['data'] = $data;

      if ($included !== []) {
         $res['included'] = $included;
      }

      return $res;
   }

   protected function getBaseAttributes(array $row): array {
      return $row;
   }

   protected function getRelations(array $row): array {
      return [];
   }

   protected function isValidParam(string $paramName, array $params) {
      if (array_key_exists($paramName, $params)) {
         return !array_search($paramName, $this->getIgnoreParams());
      }

      return false;
   }

   protected function getBoolParam(string $paramName, array $params, bool $default = true) {
      if ($params[$paramName] == '') {
         return $default;
      }

      if (array_search($params[$paramName], ['false', 'true']) === false) {
         throw new BadParameterException("Параметр $paramName должен иметь значение 'true', 'false' или пустая строка.", HttpResponse::HTTP_BAD_REQUEST);
      }

      return $params[$paramName] == 'true';
   }

   protected function getEntityName() {
      throw new NotImplementedException('Функция getEntityName() не реализована.');
   }

   protected function getApiName() {
      return $this->getEntityName();
   }

   protected function createQuery(QueryBuilder $query, array $params) {
      throw new NotImplementedException('Функция createQuery() не реализована.');
   }

   protected function createQueryById(QueryBuilder $query, array $params) {
      $this->createQuery($query, $params)
         ->where("{$query->getAlias()}.id = :id");
   }

   /*
      Функция должна возвращать список полей подлежащих обновлению в операторе UPDATE.
      Эти поля обязательно указывать в request.body
   */
   protected function getFields(): array {
      throw new NotImplementedException('Функция getFields() не реализована.');
   }

   protected function getIgnoreParams(): array {
      return [];
   }

   protected function addIncludeInfo(QueryBuilder $query, string $include): void {
      throw new BadParameterException('An endpoint does not support the include parameter.');
   }

   private function getRowData(string $apiName, array $attrs): array {
      return [
         'type' => $apiName, 
         'id' => $attrs['id'], 
         'attributes' => array_filter($attrs, function($x) { return $x != 'id'; }, ARRAY_FILTER_USE_KEY),
         'links' => [ 'self' => 'http://' . gethostname() . ':8081/' . $apiName . '/' . $attrs['id']]
      ];
   }

   private function checkIncludeParams(QueryBuilder $query, $params) {
      if ($this->isValidParam('include', $params)) {
         foreach (explode(',', $params['include']) as $inc) {
            $this->addIncludeInfo($query, $inc);
         }
      }
   }
}

?>