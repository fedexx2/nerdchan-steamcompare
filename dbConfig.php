<?php defined('DIRECTACCESS') OR die('No direct script access.');

Db::Init([ 'host' => '127.0.0.1',
           'database' => 'steamcompare',
           'username' => 'steam',
           'password' => '12345'
]);

define('APIKEY', "YOUR APIKEY HERE");