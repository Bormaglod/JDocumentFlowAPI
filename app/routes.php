<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@yandex.ru>
// License: https://opensource.org/licenses/GPL-3.0

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Connection\PostgresConnection as PostgresConnection;
use App\Exception\AccessException as AccessException;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Controller\DatabaseController as Database;
use App\Controller\UsersController as Users;
use App\Controller\MeasurementController as Measurement;
use App\Controller\OkopfController as Okopf;
use App\Controller\MaterialController as Material;

$app->post('/login', Database::class . ':login');

$app->group('/users', function (Group $group) {
   $group->get('', Users::class . ':get');
   $group->get('/{id}', Users::class . ':getById');
});

$app->group('/measurements', function (Group $group) {
   $group->get('', Measurement::class . ':get');
   $group->get('/{id}', Measurement::class . ':getById');
   $group->post('', Measurement::class . ':post');
   $group->put('/{id}', Measurement::class . ':put');
   $group->patch('/{id}', Measurement::class . ':patch');
   $group->delete('/{id}', Measurement::class . ':delete');
});

$app->group('/okopf', function (Group $group) {
   $group->get('', Okopf::class . ':get');
   $group->get('/{id}', Okopf::class . ':getById');
   $group->post('', Okopf::class . ':post');
   $group->put('/{id}', Okopf::class . ':put');
   $group->patch('/{id}', Okopf::class . ':patch');
   $group->delete('/{id}', Okopf::class . ':delete');
});

$app->group('/materials', function (Group $group) {
   $group->get('', Material::class . ':get');
});
/*$app->group('/goods', function (Group $group) {
   $group->get('', Goods::class . ':get');
   $group->get('/{id}', Goods::class . ':getById');
   
   $group->post('', Goods::class . ':post');
   $group->put('/{id}', Goods::class . ':put');
   $group->patch('/{id}', Goods::class . ':patch');
   $group->patch('/{id}/move', Goods::class . ':moveToFolder');
   $group->patch('/{id}/change-state', Goods::class . ':changeState');
   $group->delete('/{id}', Goods::class . ':delete');

   //$group->get('/materials', Goods::class . ':getMaterials');
   //$group->get('/materials/{id}', Goods::class . ':getMaterialById');
   //$group->get('/productions', Goods::class . ':getProductions');
   //$group->get('/productions/{id}', Goods::class . ':getProductionById');
});*/

?>