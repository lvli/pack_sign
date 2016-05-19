<?php
namespace Home\Model;
use Think\Model;

class LogModel extends Model{
	protected $tableName = 'log';
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
			foreach($list as &$v){
				$v['ip'] = long2ip($v['ip']);
				$v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
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