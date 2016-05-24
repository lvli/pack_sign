<?php
namespace Home\Model;
use Think\Model;

class CommonModel extends Model{

	function __construct() {
		parent::__construct();
	}

	protected function format_virus_result($result){
		if(empty($result)){
			return '';
		}

		$result = json_decode($result, true);
		$content = '';
		$search = array(
				"{name}",
				"{id}",
				"{path}",
				"{status}",
		);
		$replace = array(
				$result['name'],
				$result['id'],
				$result['path'],
		);
		if($result['status'] == 0){
			$replace['status'] = '无毒';
		}else{
			$replace['status'] = '有毒';
		}
		$content .= '<table class="table table-bordered">';
		$content .= "<tr><th>杀毒引擎</th><th>状态</th><th>病毒名称</th><th>病毒库</th><th>扫描时间</th></tr>";
		foreach($result['av_status'] as $k => $u){
			$content .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>", $k, $u['status'] == 0 ? '无毒': '有毒', $u['threat'], $u['definitions'], date('Y-m-d H:i:s', $u['scanTime']));
		}
		$content .= '</table>';
		$content = str_replace($search, $replace, $content);
		return $content;
	}

	protected function get_virus_result_count($result){
		if(empty($result)){
			return 0;
		}

		$result = json_decode($result, true);
		$count = 0;
		foreach($result['av_status'] as $k => $u){
			if($u['status'] != 0){
				$count ++;
			}
		}
		return $count;
	}

}