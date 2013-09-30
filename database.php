<?php

return init_pdo();

function init_pdo ()
{
    global $CONFIG;
    
    $dsn = "mysql:dbname={$CONFIG['db_name']};host={$CONFIG['db_host']}";
    $pdo = new PDO($dsn, $CONFIG['db_user'], $CONFIG['db_pass'], array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    ));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    return $pdo;
}

?>
