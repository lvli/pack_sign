<?php
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
define ('APP_DEBUG', true);
define('APP_PATH','./Apps/');
define('MODE_NAME', 'cli');
require './ThinkPHP/ThinkPHP.php';