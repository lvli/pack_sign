<?php
namespace Home\Model;
use Think\Model;

class ResultModel extends Model{
	protected $tableName = 'result';
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

		$result['pack_start_time'] = empty($result['pack_start_time']) ? '' : date('Y-m-d H:i:s', $result['pack_start_time']);
		$result['pack_end_time'] = empty($result['pack_end_time']) ? '' : date('Y-m-d H:i:s', $result['pack_end_time']);
		$result['sign_start_time'] = empty($result['sign_start_time']) ? '' : date('Y-m-d H:i:s', $result['sign_start_time']);
		$result['sign_end_time'] = empty($result['sign_end_time']) ? '' : date('Y-m-d H:i:s', $result['sign_end_time']);
		$result['virus_start_time'] = empty($result['virus_start_time']) ? '' : date('Y-m-d H:i:s', $result['virus_start_time']);
		$result['virus_end_time'] = empty($result['virus_end_time']) ? '' : date('Y-m-d H:i:s', $result['virus_end_time']);

		return $result;
	}

	function findAll($taskId, $pageCount){
		if(empty($taskId)){
			return array();
		}

		import('ORG.Util.Page');
		$count = $this->table->where("task_id={$taskId}")->count();
		if(!empty($count)){
			$page = new \Org\Util\Page($count, $pageCount);
			$list = $this->table->where("task_id={$taskId}")->order('id DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
		}

		if(!empty($list)){
			foreach($list as &$v){
				$v['pack_start_time'] = empty($v['pack_start_time']) ? '' : date('Y-m-d H:i:s', $v['pack_start_time']);
				$v['pack_end_time'] = empty($v['pack_end_time']) ? '' : date('Y-m-d H:i:s', $v['pack_end_time']);
				$v['sign_start_time'] = empty($v['sign_start_time']) ? '' : date('Y-m-d H:i:s', $v['sign_start_time']);
				$v['sign_end_time'] = empty($v['sign_end_time']) ? '' : date('Y-m-d H:i:s', $v['sign_end_time']);
				$v['virus_start_time'] = empty($v['virus_start_time']) ? '' : date('Y-m-d H:i:s', $v['virus_start_time']);
				$v['virus_end_time'] = empty($v['virus_end_time']) ? '' : date('Y-m-d H:i:s', $v['virus_end_time']);

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

	function save_up($task_id){
		$task_info = M('task')->where("id={$task_id}")->find();
		$data = array(
				'task_id' => $task_id,
				'pack_status' => 0,
				'sign_status' => $task_info['is_sign'] == IS_STATUS_NO ? TASK_STATUS_JUMP : TASK_STATUS_INIT,
				'virus_status' => $task_info['is_virus'] == IS_STATUS_NO ? TASK_STATUS_JUMP : TASK_STATUS_INIT,
				'virus_result' => '',
				'addtime' => time(),
		);
		if(empty($id)){
			$data['addtime'] = time();
			return $this->table->data($data)->add();
		}else{
			$data['edittime'] = time();
			return $this->table->where("id={$id}")->data($data)->save();
		}
	}

}