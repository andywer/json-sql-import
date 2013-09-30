<?php

require_once 'autoloader.php';

$CONFIG = require_once 'config.php';
$PDO = require_once 'database.php';

$parameters = $argv;
$invocationFile = array_shift($parameters);

App::init($invocationFile, $parameters, $PDO);

?>
