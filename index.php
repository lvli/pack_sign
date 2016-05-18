<?php
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
define('ROOT_PATH', dirname(__FILE__) . '/');
define('APP_DEBUG',True);
define('APP_PATH','./Apps/');
require ROOT_PATH .'/ThinkPHP/ThinkPHP.php';
