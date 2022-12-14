#!/usr/bin/php
<?php
/*
 * console.php
 *
 * Copyright 2022 vladimir <vladimir@MacBookAir>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 *
 */


# ---------------------------------------------------------------- #
#               Инициализация переменных окружения                 #
# ---------------------------------------------------------------- #
$_ENV = array_merge($_ENV, parse_ini_file(".env", true));


# ---------------------------------------------------------------- #
#             Объявление автозагрузки (стандарт PSR4)              #
# ---------------------------------------------------------------- #
//Подключаем файл с окружением ядра
include $_ENV['core']['path']."core.php";


# ---------------------------------------------------------------- #
#                     Инициализация приложения                     #
# ---------------------------------------------------------------- #
//Создаем приложение (указав диретории размещения расширений ядра, фасадов, моделей и библиотек)
//~ $APP = new APP('engine/core', ['app'=>'engine/facades', 'models'=>'app/models'], 'engine/vendor');
$APP = new APP($_ENV['core']['path'], $_ENV['facades'], $_ENV['vendor']);
//Загрузим переменные окружения
$APP->config->loadENV('.env', 'app/.env');


//~ $config = parse_ini_file("console.ini", true);
//~ print_r($config);

$method = $argv[1];
$result = $APP->console->$method(array_slice($argv, 2));

print_r($result);
