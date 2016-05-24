<?php
namespace Home\Model;
use Think\Model;

class SignModel extends Model{
	protected $tableName = 'sign_pool';
	protected  $table;

	function __construct() {
		parent::__construct();
		$this->table = M($this->tableName);
	}

	function find($id){
		if(empty($id)){
			return array();
		}
		$result = $this->table->where("id={$id}")->find();
		$result['addtime'] = date('Y-m-d H:i:s', $result['addtime']);
		if(empty($result['edittime'])){
			$result['edittime'] = '';
		}else{
			$result['edittime'] = date('Y-m-d H:i:s', $result['edittime']);
		}
		$result['pack_ip'] = long2ip($result['pack_ip']);
		if(empty($result['admin_id'])){
			$result['admin_name'] = '';
		}elseif(empty($result['admin_id'])){
			$result['admin_name'] = M('admin')->where("id={$result['admin_id']}")->getField('name');
		}else{
			$result['admin_name'] = '';
		}
		return $result;
	}

	function findOne($id, $field){
		if(empty($id)){
			return '';
		}
		$result = $this->table->where("id={$id}")->getField($field);
		return $result;
	}

	function findAll($pageCount){
		import('ORG.Util.Page');
		$count = $this->table->count();
		if(!empty($count)){
			$page = new \Org\Util\Page($count, $pageCount);
			$list = $this->table->where('is_del=0')->order('id DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
		}
		if(!empty($list)){
			$admin_arr = array();
			foreach($list as &$v){
				$v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
				if(empty($v['edittime'])){
					$v['edittime'] = '';
				}else{
					$v['edittime'] = date('Y-m-d H:i:s', $v['edittime']);
				}
				$v['pack_ip'] = long2ip($v['pack_ip']);
				if(empty($v['admin_id'])){
					$v['admin_name'] = '';
				}elseif(empty($admin_arr[$v['admin_id']])){
					$v['admin_name'] = M('admin')->where("id={$v['admin_id']}")->getField('name');
				}else{
					$v['admin_name'] = $admin_arr[$v['admin_id']];
				}
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

	function save($sign_name, $sign_path, $sign_pwd, $status, $back, $id = 0){
		$data = array(
			'sign_name' => $sign_name,
			'sign_path' => $sign_path,
			'sign_pwd' => $sign_pwd,
			'status' => $status,
			'back' => $back,
			'admin_id' => session('admin_id'),
		);
		if(empty($id)){
			$data['addtime'] = time();
			return $this->table->data($data)->add();
		}else{
			if(empty($sign_path)){
				unset($data['sign_path']);
			}
			$data['edittime'] = time();
			return $this->table->where("id={$id}")->data($data)->save();
		}
	}

	function delete($id){
		$info =  $this->table->where("id={$id}")->find();
		copy($info['sign_path'], $info['sign_path'] . '_del');
		unlink($info['sign_path']);
		$status = $this->table->where("id={$id}")->data(array(
				'status' => 1,
				'is_del' => 1,
		))->save();
		return $status;
	}

}