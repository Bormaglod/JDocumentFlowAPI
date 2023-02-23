<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Tuupola\Middleware\JwtAuthentication;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\Factory\Psr17\SlimHttpPsr17Factory;

require __DIR__ . '/../vendor/autoload.php';

$responseFactory = new Psr17Factory();
$app = AppFactory::create(
   SlimHttpPsr17Factory::createDecoratedResponseFactory(
      $responseFactory, $responseFactory
  )
);

require __DIR__ . '/../app/routes.php';

$app->options('/{routes:.+}', function ($request, $response, $args) {
   return $response;
});

$app->add(function ($request, $handler) {
   $response = $handler->handle($request);
   return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->add(new JwtAuthentication([
   "secret" => "W09mjyOHBs",
   "secure" => false,
   "ignore" => [
      '/login'
   ],
   "algorithm" => ["HS256"],
   "error" => function ($response, $arguments) {
      $data["status"] = "error";
      $data["message"] = $arguments["message"];
      return $response
         ->withHeader("Content-Type", "application/json")
         ->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
   }
 ]));

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
   throw new HttpNotFoundException($req);
});

$app->run();
?>