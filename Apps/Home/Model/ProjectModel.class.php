<?php
namespace Home\Model;
use Think\Model;

class ProjectModel extends Model{
	protected $tableName = 'project';
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

	function findAll($pageCount){
		import('ORG.Util.Page');
		$count = $this->table->count();
		if(!empty($count)){
			$page = new \Org\Util\Page($count, $pageCount);
			$list = $this->table->order('id DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
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

	function save($project_name, $pack_ip, $pack_port, $pack_env, $pack_path, $pack_workpath, $back, $id = 0){
		$data = array(
			'project_name' => $project_name,
			'pack_ip' => $pack_ip,
			'pack_port' => $pack_port,
			'pack_env' => $pack_env,
			'pack_path' => $pack_path,
			'pack_workpath' => $pack_workpath,
			'back' => $back,
			'admin_id' => session('admin_id'),
		);
		if(empty($id)){
			$data['addtime'] = time();
			return $this->table->data($data)->add();
		}else{
			$data['edittime'] = time();
			return $this->table->where("id={$id}")->data($data)->save();
		}
	}

	function delete($id){
		return $this->table->where("id={$id}")->delete();
	}

}