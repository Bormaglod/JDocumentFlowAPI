<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@yandex.ru>
// License: https://opensource.org/licenses/GPL-3.0

use Dotenv\Dotenv;

// https://stackoverflow.com/questions/63813272/php-getenv-always-returns-false/65652649#65652649
$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

?>