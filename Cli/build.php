<?php
//Dev
define("DS",DIRECTORY_SEPARATOR);
define("PKG_FILE_PATH", "C:".DS."digital".DS);
define("BUIDL_PATH",'C:\\code\\ggg\\');

// 10.1.60.34 Test
// define("PKG_FILE_PATH", 'D:\\digital\\remote\\');

// Production
// define("PKG_FILE_PATH", 'D:\\digital\\');

$path = str_replace("/", '\\',$_GET["path"]);

if (strpos($path,'pub') !== false)
{
	$path = preg_replace('/^pub/i', '', $path);
}

$domain = $_GET["domain"];
$service = isset($_GET['service']) ? $_GET['service'] : 'downloader' ;
$params = '';

if($domain)
{
	$params .= ' -f '.$domain ;
}
$_path = sprintf(PKG_FILE_PATH."%s" ,$path);
$params .= ' -e '.$service.' -o '.$_path.' -t 1';

// $file_params = $_GET["params"];
$buildToolCmd = '';
// 文件名

if(!file_exists($_path))
{
	mkdir($_path, 0777, true);
}

$buildTool = '"C:\\Program Files (x86)\\Python35-32\\python.exe" '.BUIDL_PATH.'buildtools\\AutoBuild\\AutoBuild\\build_signle.py ';
$buildToolCmd .= sprintf(" %s", $buildTool);
$buildToolCmd .= sprintf(" %s" ,$params);
$buildToolCmd = iconv('utf-8','gbk',$buildToolCmd);
$buildToolCmd = str_replace("/", '\\',$buildToolCmd);
$buildToolCmd .= sprintf(" %s", " > build.log");

//$buildToolCmd_test = '"C:\\Program Files (x86)\\Python35-32\\python.exe" c:\\code\\ggg\\test.py';
//$buildToolCmd = '"C:\\Program Files (x86)\\Python35-32\\python.exe" C:\\code\\ggg\\buildtools\\AutoBuild\\AutoBuild\\build_signle.py -f yessearches -e downloader -o C:\digital\2016\04\13\zp\ -t 1';

//file_put_contents(PKG_FILE_PATH."build.txt",$buildToolCmd."\r\n",FILE_APPEND);
try {
	putenv("PATH=C:\\Program Files (x86)\\Python35-32\\Scripts\\;C:\\Program Files (x86)\\Python35-32\\;%SystemRoot%\\system32;%SystemRoot%;%SystemRoot%\\System32\\Wbem;%SYSTEMROOT%\\System32\\WindowsPowerShell\\v1.0\\;C:\\Program Files (x86)\\Windows Kits\\8.1\\Windows Performance Toolkit\\;C:\\Program Files\\Microsoft SQL Server\\110\\Tools\\Binn\\;C:\\Program Files (x86)\\Microsoft SDKs\\TypeScript\\1.0\\;C:\\Program Files\\Microsoft SQL Server\\120\\Tools\\Binn\\;C:\\Program Files\\Git\\cmd");
	// 执行命令
	chdir(BUIDL_PATH);
	system($buildToolCmd, $ret);

	// 判断目录存在则生成
	$build_path = $_path.$domain."_signle_0";
	file_put_contents(PKG_FILE_PATH."build.txt",$buildToolCmd."\r\n",FILE_APPEND);
	file_put_contents(PKG_FILE_PATH."build.txt","ret:".$ret."\r\n",FILE_APPEND);
	if (is_dir($build_path) || $ret == 1)
	{
		echo 'OK';
		return;
	}
	else
	{
		echo 'fail';
	}
} catch (Exception $e) {
	print_r($e->getTraceAsString());
	echo 'fail';
}