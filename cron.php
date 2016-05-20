<?php
if(PHP_SAPI != 'cli'){
	exit('请在命令行执行');
}

$action = $argv[1];
if(empty($action)){
	exit('参数错误');
}
$action =  'Home/CronVirus/run';

$domian = 'http://www.packsign.com/';
$result = curl_cron($domian . $action);
echo "==========================", PHP_EOL;
echo date("Y-m-d H:i:s"), PHP_EOL;
echo json_encode($result), PHP_EOL;


function curl_cron($url){
	$conn = curl_init();
	curl_setopt($conn, CURLOPT_URL, $url);
	curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($conn, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($conn, CURLOPT_FAILONERROR, 1);
	curl_setopt($conn, CURLOPT_TIMEOUT, 30);
	$result = array(
		'return' => curl_exec($conn),
		'error_no' => curl_errno($conn),
		'error' => curl_error($conn),
	);
	curl_close($conn);
	return $result;
}

