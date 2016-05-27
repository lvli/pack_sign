<?php
namespace Home\Model;
use Think\Model;

class ListUploadModel extends CommonModel{
	protected $tableName = 'list_new';
	protected  $table;

	function __construct() {
		parent::__construct();
		$this->table = M($this->tableName);
	}

	function findAll($pageCount, $search_name = ''){
		$where = '1=1';
		$search_name = strtoupper($search_name);
		if(!empty($search_name)){
			$where .= " AND upper(`file_name`) LIKE '%{$search_name}%'";
		}
		import('ORG.Util.Page');
		$count = $this->table->where($where)->count();
		if(!empty($count)){
			$page = new \Org\Util\Page($count, $pageCount);
			$list = $this->table->where($where)->order('id DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
		}
		if(!empty($list)){
			$sign_list_arr = M('sign_pool')->field('id,sign_name')->select();
			$sign_list = array();
			foreach($sign_list_arr as $s){
				$sign_list[$s['id']] = $s['sign_name'];
			}
			foreach($list as &$v){
				$v['scan_time'] = date('Y-m-d H:i:s', $v['scan_time']);
				if($v['status'] == STATUS_CDN_UPLOADED){
					$v['url'] = sprintf("https://%s/%s/%s", C('CDN_DOWANLOAD_URL'), C('PUT_CDN_DIR'), basename($v['file_path']));
				}else{
					$v['url'] = '';
				}
				$v['status_int'] = 	$v['status'];
				$v['status'] = $this->get_status_name($v['status_int']);
				$v['status_real'] = $this->get_status_real_name($v['status_int']);
				if(!empty($v['confirm_sign']) && $v['status_int'] == STATUS_SIGN_STILL_VIRUS_CHECKED){
					$v['status'] = '指定的签名有毒';
				}

				$sign_used_arr = explode(',', $v['sign_used']);
				$sign_used = '';
				foreach($sign_used_arr as $u){
					$sign_used .= ','. $sign_list[$u];
				}
				$v['sign_used'] = trim($sign_used, ',');
				$v['sign_used_now'] = array_pop(explode(',', $v['sign_used']));
				$v['confirm_sign']  = $sign_list[$v['confirm_sign']];
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
		$info = M('list_new')->where("id={$id}")->find();
		$info['file_name'] = basename($info['file_path']);
		return $info;
	}

	public function virus($id){
		$list = M('detail_new')->where("list_id={$id}")->order('id DESC')->select();
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
		$list = M('detail_new')->where("id={$id}")->find();
		$list['virus_result'] = $this->format_virus_result($list['virus_result']);
		return $list;
	}

	public function jump_step($id){
		$old_status = M('list_new')->where("id={$id}")->getField('status');
		if($old_status == STATUS_PROGRAM_VIRUS){
			$status = STATUS_PROGRAM_VIRUS_JUMP;
		}elseif($old_status == STATUS_SIGN_VIRUS || $old_status == STATUS_SIGN_STILL_VIRUS_NO_CHECK || $old_status == STATUS_SIGN_STILL_VIRUS_CHECKED){
			$status = STATUS_SIGN_VIRUS_JUMP;
		}else{
			return false;
		}
		$status = M('list_new')->where("id={$id}")->data(array(
			'status' => $status,
			'is_jump' => 1,
		))->save();
		return $status;
	}

	public  function confirm_sign($id, $sign){
		$list_status =  M('list_new')->where("id={$id}")->getField('status');
		$data = array(
			'confirm_sign' => $sign,
		);
		$status_arr = array(
			STATUS_INIT,
			STATUS_PROGRAM_VIRUS,
		);
		if(!in_array($list_status, $status_arr)){
			$data['status'] = STATUS_INIT;
			$data['sign_used'] = '';
		}
		M('detail_new')->where("list_id={$id} AND status=0")->delete();
		$status = M('list_new')->where("id={$id}")->data($data)->save();
		return $status;
	}

	public function upload($name, $ver, $description, $file_path, $sign){
		$data = array(
			'mains_id' => 0,
			'name' => $name,
			'ver' => $ver,
			'description' => $description,
			'file_path' => $file_path,
			'file_name' => basename($file_path),
			'confirm_sign' => $sign,
			'status' => 0,
			'scan_time' => time(),
			'email_status' => 0,
		);
		return $this->table->data($data)->add();
	}

	private function get_status_name($status){
		$status_arr = array(
			STATUS_INIT => '尚未开始',
			STATUS_PROGRAM_NO_VIRUS => '程序无毒',
			STATUS_SIGN => '处理中',
			STATUS_SIGN_NO_VIRUS => '签名无毒',
			STATUS_PROGRAM_VIRUS => '程序有毒',
			STATUS_SIGN_VIRUS => '签名有毒',
			STATUS_SIGN_STILL_VIRUS_NO_CHECK => '处理中',
			STATUS_SIGN_STILL_VIRUS_CHECKED => '处理中',
			STATUS_CRON_DEAL => '定时任务处理中',
			STATUS_CDN_UPLOADED => '已上传CDN',
			STATUS_PROGRAM_VIRUS_JUMP => '处理中',
			STATUS_SIGN_VIRUS_JUMP => '处理中',
		);

		return isset($status_arr[$status]) ? $status_arr[$status] : '';
	}

	private function get_status_real_name($status){
		$status_arr = array(
				STATUS_INIT => '尚未开始',
				STATUS_PROGRAM_NO_VIRUS => '程序无毒',
				STATUS_SIGN => '签名',
				STATUS_SIGN_NO_VIRUS => '签名无毒',
				STATUS_PROGRAM_VIRUS => '程序有毒',
				STATUS_SIGN_VIRUS => '签名有毒',
				STATUS_SIGN_STILL_VIRUS_NO_CHECK => '签名后依然有毒,需要用微软程序验证签名是否有毒',
				STATUS_SIGN_STILL_VIRUS_CHECKED => '确认签名有毒,需要更换签名再次扫描的',
				STATUS_CRON_DEAL => '定时任务处理中',
				STATUS_CDN_UPLOADED => '已上传CDN',
				STATUS_PROGRAM_VIRUS_JUMP => '跳过程序扫毒步骤开始签名',
				STATUS_SIGN_VIRUS_JUMP => '跳过签名扫毒步骤开始上传CDN',
		);

		return isset($status_arr[$status]) ? $status_arr[$status] : '';
	}

	function delete($id){
		$status = $this->table->where("id={$id}")->delete();
		return $status;
	}

}