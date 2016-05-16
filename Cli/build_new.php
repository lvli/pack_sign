<?php
/**
 * "C:\Program Files (x86)\Python35-32\python.exe" C:\code\ggg\buildtools\AutoBuild\AutoBuild\build_signle.py   -f hohosearch -e downloader -o C:\digital\mainv2\2016\04\14\182442\258\ -t 1  > build.log
 * 需要的参数
 * 	环境变量 env
 *	命令行路径和参数 path
 * 	工作目录 work_path
 */

class Cron{
	const CALLBACK_URL = '';
	const TIMEOUT = 10;
	private $pkg_file_path = '';
	private $default_env = '';
	private $default_path = '';
	private $env = '';
	private $path = '';
	private $work_path = '';

	function run(){
		$this->init();
		$this->get_params();
		$this->exec();
		$this->result();
		$this->end();
	}

	private function exec(){
		$build_tool_cmd = sprintf(" %s > build.log" , $this->path);
		$build_tool_cmd = iconv('utf-8','gbk', $build_tool_cmd);
		$build_tool_cmd = str_replace("/", '\\', $build_tool_cmd);

		try {
			putenv($this->env);
			chdir($this->work_path);
			system($build_tool_cmd, $ret);

			// 判断目录存在则生成
			file_put_contents($this->pkg_file_path . "build.txt", $build_tool_cmd . PHP_EOL, FILE_APPEND);
			file_put_contents($this->pkg_file_path . "build.txt", "ret:" . $ret . PHP_EOL, FILE_APPEND);
			if ($ret == 1)
			{
				$this->log('bulid success', 'info');
			}
			else
			{
				$this->log('bulid fail', 'error');
			}
		} catch (Exception $e) {
			$this->log($e->getTraceAsString(), 'error');
		}
	}

	private function result(){

	}

	private function post($post_arr){
		if(empty($post_arr)){
			return false;
		}

		$mh = curl_multi_init();
		$res = array();
		$conn = array();
		foreach ($post_arr as $i => $v) {
			$conn[$i] = curl_init();
			curl_setopt($conn[$i], CURLOPT_URL, self::CALLBACK_URL);
			curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($conn[$i], CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($conn[$i], CURLOPT_FAILONERROR, 1);
			curl_setopt($conn[$i], CURLOPT_TIMEOUT, self::TIMEOUT);
			curl_setopt($conn[$i], CURLOPT_POST, 1);
			curl_setopt($conn[$i], CURLOPT_POSTFIELDS, $v['data']);

			curl_multi_add_handle($mh, $conn[$i]);
		}

		do{
			curl_multi_exec($mh,$active);
		}while($active);

		foreach ($post_arr as $i => $v) {
			$res[$i] = curl_multi_getcontent($conn[$i]);
			curl_close($conn[$i]);
		}

		return $res;
	}

	private function get_params(){
		$this->env = isset($_REQUEST['env']) ? $_REQUEST['env'] : '';
		$this->path = isset($_REQUEST['path']) ? $_REQUEST['path'] : $this->default_path;
		$this->work_path = isset($_REQUEST['work_path']) ? $_REQUEST['work_path'] : '';

		//TEST TODO
		//$this->env = '';
		//$this->work_path  = '';
		//TEST

		if(empty($this->env) || empty($this->path) ||empty($this->work_path) || is_dir($this->path) || is_dir($this->work_path)){
			$this->log('参数错误', 'error');
		}
		$this->env = $this->default_env . PATH_SEPARATOR . $this->env;
	}

	private function init(){
		$this->pkg_file_path = 'C:' . PATH_SEPARATOR . "digital" . PATH_SEPARATOR;
		$this->default_env = 'PATH=C:\Program Files (x86)\Python35-32\Scripts\;C:\Program Files (x86)\Python35-32\;%SystemRoot%\system32;%SystemRoot%;%SystemRoot%\System32\Wbem;%SYSTEMROOT%\System32\WindowsPowerShell\v1.0\;C:\Program Files (x86)\Windows Kits\8.1\Windows Performance Toolkit\;C:\Program Files\Microsoft SQL Server\110\Tools\Binn\;C:\Program Files (x86)\Microsoft SDKs\TypeScript\1.0\;C:\Program Files\Microsoft SQL Server\120\Tools\Binn\;C:\Program Files\Git\cmd;';
		$this->default_path = '"C:\Program Files (x86)\Python35-32\python.exe" C:\code\ggg\buildtools\AutoBuild\AutoBuild\build_signle.py   -f hohosearch -e downloader -o C:\digital\mainv2\2016\04\14\182442\258\ -t 1  > build.log';
	}

	private function end(){

	}

	function log($log, $level){
		if($level == 'info'){
			echo $log;
		}else{
			echo $log;
			exit;
		}
	}
}

$cron_script = new Cron();
$cron_script->run();