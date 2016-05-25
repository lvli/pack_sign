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

	function findAll($pageCount){
		import('ORG.Util.Page');
		$count = $this->table->count();
		if(!empty($count)){
			$page = new \Org\Util\Page($count, $pageCount);
			$list = $this->table->order('id DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
		}
		if(!empty($list)){
			$sign_list_arr = M('sign_pool')->field('id,sign_name')->select();
			$sign_list = array();
			foreach($sign_list_arr as $s){
				$sign_list[$s['id']] = $s['sign_name'];
			}
			foreach($list as &$v){
				$v['file_path'] = basename($v['file_path']);
				$v['scan_time'] = date('Y-m-d H:i:s', $v['scan_time']);
				if($v['status'] == STATUS_CDN_UPLOADED){
					$v['url'] = sprintf("https://%s/%s/%s", C('CDN_DOWANLOAD_URL'), C('PUT_CDN_DIR'), basename($v['file_path']));
				}else{
					$v['url'] = '';
				}
				$v['status_int'] = 	$v['status'];
				$v['status'] = $this->get_status_name($v['status']);

				$sign_used_arr = explode(',', $v['sign_used']);
				$sign_used = '';
				foreach($sign_used_arr as $u){
					$sign_used .= ','. $sign_list[$u];
				}
				$v['sign_used'] = trim($sign_used, ',');
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
			STATUS_PROGRAM_NO_VIRUS,
			STATUS_PROGRAM_VIRUS,
			STATUS_PROGRAM_VIRUS_JUMP,
		);
		if(!in_array($list_status, $status_arr)){
			$data['status'] = STATUS_SIGN_STILL_VIRUS_CHECKED;
		}
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
			STATUS_SIGN => '签名',
			STATUS_SIGN_NO_VIRUS => '签名无毒',
			STATUS_PROGRAM_VIRUS => '程序有毒',
			STATUS_SIGN_VIRUS => '签名有毒',
			STATUS_SIGN_STILL_VIRUS_NO_CHECK => '等待处理',
			STATUS_SIGN_STILL_VIRUS_CHECKED => '等待处理',
			STATUS_CDN_UPLOADED => '已上传CDN',
			STATUS_PROGRAM_VIRUS_JUMP => '等待处理',
			STATUS_SIGN_VIRUS_JUMP => '等待处理',
		);

		return isset($status_arr[$status]) ? $status_arr[$status] : '';
	}

	function delete($id){
		$status = $this->table->where("id={$id}")->delete();
		return $status;
	}

}