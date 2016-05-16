<?php
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
define('ROOT_PATH', dirname(__FILE__) . '/');
define ('APP_DEBUG', true);
define('APP_PATH', './Apps/');
define('MODE_NAME', 'cli');

$depr = DIRECTORY_SEPARATOR;
$path   = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
$params = array();
if(!empty($path)) {
	$params = explode($depr, trim($path, $depr));
}
!empty($params) ? $_GET['g'] = array_shift($params) : NULL;
!empty($params) ? $_GET['m'] = array_shift($params) : NULL;
!empty($params) ? $_GET['a'] = array_shift($params) : NULL;
if(count($params) > 1) {
	preg_replace('@(\w+),([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', implode(',',$params));
}
require ROOT_PATH . '/ThinkPHP/ThinkPHP.php';