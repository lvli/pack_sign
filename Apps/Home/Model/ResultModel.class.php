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

}