<?php
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
define('ROOT_PATH', dirname(__FILE__) . '/');
define ('APP_DEBUG', true);
define('APP_PATH', './Apps/');
define('MODE_NAME', 'cli');
define('APP_MODE', 'cli');

require ROOT_PATH . '/ThinkPHP/ThinkPHP.php';