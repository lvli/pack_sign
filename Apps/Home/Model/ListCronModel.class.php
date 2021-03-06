<?php
namespace Home\Model;
use Think\Model;

class ListCronModel extends CommonModel{
	protected $tableName = 'list_cron';
	protected  $table;

	function __construct() {
		parent::__construct();
		$this->table = M($this->tableName);
	}

	function getScanTimes(){
		$scan_times = (int)$this->table->order('id DESC')->getField('scan_times');
		if($scan_times <= 0){
			$scan_list = array();
		}elseif($scan_times == 1){
			$scan_list = array(1);
		}else{
			$scan_list = range(1, $scan_times);
		}
		$scan_list = array_reverse($scan_list);

		return $scan_list;
	}

	function findAll($pageCount, $search_name = '', $scan_times = 0){
		$where = '1=1';
		$search_name = strtoupper($search_name);
		if(!empty($search_name)){
			$where .= " AND upper(`file_name`) LIKE '%{$search_name}%'";
		}

		if(!empty($scan_times)){
			$where .= " AND scan_times={$scan_times}";
		}

		import('ORG.Util.Page');
		$count = $this->table->where($where)->count();
		if(!empty($count)){
			$page = new \Org\Util\Page($count, $pageCount);
			$list = $this->table->where($where)->order('id DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
		}
		if(!empty($list)){
			$sign_list_arr = M('sign_pool')->where('status=0')->field('id,sign_name')->select();
			$sign_list = array();
			foreach($sign_list_arr as $s){
				$sign_list[$s['id']] = $s['sign_name'];
			}
			foreach($list as &$v){
				$v['file_path'] = basename($v['file_path']);
				$v['scan_time'] = date('Y-m-d H:i:s', $v['scan_time']);
				$v['status'] = $this->get_status_name($v['status']);
				$sign_used_arr = explode(',', $v['sign_used']);
				$sign_used = '';
				foreach($sign_used_arr as $u){
					$sign_used .= ','. $sign_list[$u];
				}
				$v['sign_used'] = trim($sign_used, ',');
				$v['url'] = sprintf("https://%s/%s/%s", C('CDN_DOWANLOAD_URL'), C('PUT_CDN_DIR'), basename($v['file_path']));
			}
			return array(
				"list" => $list,
				"pagination" => $page->show(),
			);
		}else{
			return array(
				"list" => array(),
				"pagination" => '',
			);
		}
	}

	public function find($id){
		$info = M('list_cron')->where("id={$id}")->find();
		$info['file_name'] = basename($info['file_path']);
		return $info;
	}

	public function virus($id){
		$list = M('detail_cron')->where("list_id={$id}")->order('id DESC')->select();
		foreach($list as &$v){
			$v['virus_count'] = $this->get_virus_result_count($v['virus_result']);
			$v['begin_time'] = date('Y-m-d H:i:s', $v['begin_time']);
			if(empty($v['end_time'])){
				$v['end_time'] = '处理中';
			}else{
				$v['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
			}
		}
		return $list;
	}

	public function virus_detail($id){
		$list = M('detail_cron')->where("id={$id}")->find();
		$list['virus_result'] = $this->format_virus_result($list['virus_result']);
		return $list;
	}

	private function get_status_name($status){
		$status_arr = array(
			STATUS_INIT => '尚未开始',
			STATUS_PROGRAM_NO_VIRUS => 'CDN文件无毒',
			STATUS_PROGRAM_VIRUS => 'CDN文件有毒',
		);

		return isset($status_arr[$status]) ? $status_arr[$status] : '';
	}

}