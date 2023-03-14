<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@yandex.ru>
// License: https://opensource.org/licenses/GPL-3.0

use Psr\Http\Message\{
   ServerRequestInterface as Request,
   ResponseInterface as Response
};
use App\Connection\PostgresConnection as PostgresConnection;
use App\Exception\AccessException as AccessException;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Controller\{
   LoginController as Login,
   UsersController as Users,
   MeasurementController as Measurement,
   OkopfController as Okopf,
   MaterialController as Material,
   GoodsController as Goods,
   CalculationController as Calculation
};

$app->post('/login', Login::class . ':login');

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
   $group->get('/{id}', Material::class . ':getById');
});

$app->group('/goods', function (Group $group) {
   $group->get('', Goods::class . ':get');
   $group->get('/{id}', Goods::class . ':getById');
   $group->get('/{id}/calculations', Calculation::class . ':getByOwner');
});

$app->group('/calculations', function (Group $group) {
   $group->get('/{id}', Calculation::class . ':getById');
});

?>