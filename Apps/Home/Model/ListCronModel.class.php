<?php
namespace Home\Model;
use Think\Model;

class ListCronModel extends Model{
	protected $tableName = 'list_cron';
	protected  $table;

	function __construct() {
		parent::__construct();
		$this->table = M($this->tableName);
	}

	function findAll($pageCount){
		import('ORG.Util.Page');
		$count = $this->table->count();
		if(!empty($count)){
			$page = new \Org\Util\Page($count, $pageCount);
			$list = $this->table->order('id DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
		}
		if(!empty($list)){
			$sign_list_arr = M('sign_pool')->where('status=0')->field('id,sign_name')->select();
			$sign_list = array();
			foreach($sign_list_arr as $s){
				$sign_list[$s['id']] = $s['sign_name'];
			}
			foreach($list as &$v){
				$v['scan_time'] = date('Y-m-d H:i:s', $v['scan_time']);
				$v['status'] = $this->get_status_name($v['status']);
				$sign_used_arr = explode(',', $v['sign_used']);
				$sign_used = '';
				foreach($sign_used_arr as $u){
					$sign_used .= ','. $sign_list[$u];
				}
				$v['sign_used'] = trim($sign_used, ',');
				$v['last_virus_result'] = M('detail_cron')->where('list_id='.$v['id'])->order('id DESC')->getField('virus_result');
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

	private function get_status_name($status){
		$status_arr = array(
			STATUS_INIT => '尚未开始',
			STATUS_PROGRAM_NO_VIRUS => '程序无毒',
			STATUS_SIGN => '签名',
			STATUS_SIGN_NO_VIRUS => '签名无毒',
			STATUS_PROGRAM_VIRUS => '程序有毒',
			STATUS_SIGN_VIRUS => '签名有毒',
			STATUS_SIGN_STILL_VIRUS_NO_CHECK => '签名后依然有毒,需要用微软程序验证签名是否有毒',
			STATUS_SIGN_STILL_VIRUS_CHECKED => '确认签名有毒,需要更换签名再次扫描的',
		);

		return isset($status_arr[$status]) ? $status_arr[$status] : '';
	}

}